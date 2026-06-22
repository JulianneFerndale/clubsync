<?php

namespace App\Jobs;

use App\Exports\ChedReportExport;
use App\Models\ChedReport;
use App\Models\ClubActivity;
use App\Models\ClubMember;
use App\Models\Semester;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class GenerateChedReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $activityId) {}

    public function handle(): void
    {
        $activity = ClubActivity::with('club.adviserUser', 'creator')->find($this->activityId);

        if (! $activity || ! $activity->club) {
            return;
        }

        $reportType = $activity->activity_type === 'acle' ? 'acle' : 'community_service';

        $semester = Semester::where('start_date', '<=', $activity->date)
            ->where('end_date', '>=', $activity->date)
            ->first();

        $participants = ClubMember::where('club_id', $activity->club_id)
            ->where('registration_status', 'approved')
            ->with('user')
            ->get()
            ->filter(function (ClubMember $member) use ($semester) {
                if (! $semester) {
                    return true;
                }

                $status = $member->semesterStatuses()
                    ->where('semester_id', $semester->id)
                    ->orderByDesc('created_at')
                    ->first();

                return ! $status || $status->semester_status === 'active';
            });

        $data = [
            'club_name'       => $activity->club->name,
            'adviser_name'    => $activity->club->adviserUser?->name ?? 'Not assigned',
            'activity_title'  => $activity->title,
            'activity_type'   => $activity->activity_type,
            'date'            => $activity->date->format('F j, Y'),
            'time'            => $activity->time_start . ' - ' . $activity->time_end,
            'venue'           => $activity->venue,
            'objectives'      => $activity->purpose,
            'description'     => $activity->description,
            'officer_in_charge' => $activity->creator?->name ?? 'Unknown',
            'participant_count' => $participants->count(),
            'participants'    => $participants->map(fn (ClubMember $m) => $m->user?->name ?? 'Unknown')->values(),
        ];

        $directory = 'reports/' . $activity->id;

        $pdfPath = $directory . '/ched-report.pdf';
        Pdf::loadView('reports.ched-pdf', $data)->save($pdfPath, 'local');

        $xlsxPath = $directory . '/ched-report.xlsx';
        Excel::store(new ChedReportExport($data), $xlsxPath, 'local');

        ChedReport::create([
            'club_activity_id' => $activity->id,
            'club_id'          => $activity->club_id,
            'report_type'      => $reportType,
            'pdf_path'         => $pdfPath,
            'xlsx_path'        => $xlsxPath,
        ]);
    }
}
