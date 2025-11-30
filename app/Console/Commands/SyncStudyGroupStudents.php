<?php

namespace App\Console\Commands;

use App\Models\Hrms\StudyGroup;
use App\Models\Hrms\StudentEducationDetail;
use Illuminate\Console\Command;

class SyncStudyGroupStudents extends Command
{
	protected $signature = 'study-groups:sync-students';
	protected $description = 'Sync students to study groups based on their study centre';

	public function handle(): int
	{
		$this->info('Syncing students to study groups...');

		$groups = StudyGroup::whereNotNull('study_centre_id')->get();
		$synced = 0;

		foreach ($groups as $group) {
			$studentIds = StudentEducationDetail::where('study_centre_id', $group->study_centre_id)
				->pluck('student_id')
				->filter()
				->unique()
				->toArray();

			$group->students()->sync($studentIds);
			$count = count($studentIds);
			$this->line("Group '{$group->name}': synced {$count} students");
			$synced++;
		}

		$this->info("Successfully synced {$synced} study groups.");
		return 0;
	}
}
