<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BroadcastSchedulerService;

class SendScheduledRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saturnus:send-scheduled-reminders {--force : Paksa jalankan tanpa memeriksa rentang waktu dan interval jadwal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim email pengingat pending approval SATURNUS secara otomatis berdasarkan jadwal timing yang dikonfigurasi';

    /**
     * Execute the console command.
     */
    public function handle(BroadcastSchedulerService $schedulerService): int
    {
        $isForce = $this->option('force');
        $this->info("Memeriksa status jadwal pengingat email otomatis SATURNUS...");

        if (!$isForce && !$schedulerService->shouldRunNow()) {
            $settings = $schedulerService->getSettings();
            $enabledText = !empty($settings['is_enabled']) ? 'Aktif' : 'Nonaktif';
            $nextRun = $settings['next_run_at'] ?: '-';
            $this->line("Scheduler dilewati: Tidak memenuhi kriteria timing saat ini (Status: {$enabledText}, Jam: {$settings['start_time']}-{$settings['end_time']}, Interval: {$settings['interval_hours']} jam, Jadwal Berikutnya: {$nextRun}).");
            return Command::SUCCESS;
        }

        $this->info("Menjalankan pengiriman broadcast email otomatis...");
        $result = $schedulerService->executeBroadcast($isForce, 'Cron Task Scheduler');

        $this->info("Hasil: {$result['message']}");
        $this->table(
            ['Penerima', 'Email', 'Departemen', 'Role', 'Jumlah Form', 'Status'],
            array_map(fn($d) => [
                $d['recipient_name'],
                $d['recipient_email'],
                $d['department'] ?? '-',
                $d['role'] ?? '-',
                $d['form_count'],
                $d['status'],
            ], $result['details'] ?? [])
        );

        return Command::SUCCESS;
    }
}
