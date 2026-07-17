<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use App\Services\AuthManager;
use App\Services\SessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(
        private AuthManager $auth,
        private SessionManager $session,
    ) {}

    public function showStep1(): View
    {
        return view('auth.register');
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'edp_number'    => ['required', 'string', 'max:20'],
            'email'         => ['required', 'email', 'ends_with:sccpag.edu.ph', 'unique:users,email'],
            'password'      => ['required', 'confirmed', Password::min(8)],
            'mobile_number' => ['required', 'string', 'max:20'],
            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'terms'         => ['accepted'],
        ], [
            'email.ends_with'  => 'Only Saint Columban College institutional emails (@sccpag.edu.ph) are accepted.',
            'email.unique'     => 'An account with this email already exists.',
            'terms.accepted'   => 'You must accept the Terms and Conditions to continue.',
        ]);

        session([
            'register.first_name'    => $validated['first_name'],
            'register.last_name'     => $validated['last_name'],
            'register.edp_number'    => $validated['edp_number'],
            'register.email'         => strtolower($validated['email']),
            'register.password'      => $validated['password'],
            'register.mobile_number' => $validated['mobile_number'],
        ]);

        // Store the optional profile photo now and carry its URL through the
        // remaining steps (a file can't live in the session).
        if ($request->hasFile('photo')) {
            session(['register.photo_url' => Storage::url($request->file('photo')->store('profile-photos', 'public'))]);
        }

        return redirect()->route('register.department');
    }

    public function showDepartment(): RedirectResponse|View
    {
        if (! session('register.email')) {
            return redirect()->route('register');
        }

        $departments = Department::orderBy('short_name')->get();

        return view('auth.register.department', compact('departments'));
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        session(['register.department_id' => $request->department_id]);

        return redirect()->route('register.course');
    }

    public function showCourse(): RedirectResponse|View
    {
        if (! session('register.department_id')) {
            return redirect()->route('register.department');
        }

        $department = Department::with('courses')->findOrFail(session('register.department_id'));

        return view('auth.register.course', compact('department'));
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        $course = Course::findOrFail($request->course_id);

        if ((int) $course->department_id !== (int) session('register.department_id')) {
            return back()->withErrors(['course_id' => 'Invalid course selection.']);
        }

        $email    = session('register.email');
        $password = session('register.password');

        // Create the Firebase Auth user
        try {
            $firebaseData = $this->auth->registerWithFirebase($email, $password);
        } catch (\Throwable $e) {
            Log::error('Firebase registration failed for ' . $email . ': ' . $e->getMessage());
            return back()->withErrors([
                'course_id' => 'We could not complete your registration. Please try again.',
            ]);
        }

        $user = User::create([
            'first_name'    => session('register.first_name'),
            'last_name'     => session('register.last_name'),
            'edp_number'    => session('register.edp_number'),
            'email'         => $email,
            'password'      => $password,
            'mobile_number' => session('register.mobile_number'),
            'department_id' => session('register.department_id'),
            'course_id'     => $request->course_id,
            'firebase_uid'  => $firebaseData['localId'],
            'role'          => 'member',
            'profile_photo_url' => session('register.photo_url'),
        ]);

        // Auto-enroll the new user into every academic club mapped to their course
        $academicClubs = Club::where('club_type', 'Academic')
                             ->where('is_active', true)
                             ->whereHas('courses', fn ($q) => $q->where('courses.id', $course->id))
                             ->get();

        foreach ($academicClubs as $academicClub) {
            ClubMember::firstOrCreate(
                ['club_id' => $academicClub->id, 'user_id' => $user->id],
                ['role' => 'Member', 'date_joined' => now(), 'joined_at' => now()],
            );
        }

        session()->forget([
            'register.first_name', 'register.last_name', 'register.edp_number',
            'register.email', 'register.password', 'register.mobile_number',
            'register.department_id', 'register.photo_url',
        ]);

        $request->session()->regenerate();

        $this->session->store(
            $firebaseData['idToken'],
            $firebaseData['localId'],
            'member',
            $user->id,
            refreshToken: $firebaseData['refreshToken'] ?? null,
        );

        return redirect()->route('member.dashboard');
    }
}
