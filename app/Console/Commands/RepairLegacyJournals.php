<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\User;
use App\Services\LegacyJournalRepairService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class RepairLegacyJournals extends Command
{
    protected $signature = 'accounting:repair-legacy-journals
        {--job= : Nomor Job persis}
        {--document= : Nomor CN/PR/DN persis}
        {--all : Proses semua job}
        {--apply : Terapkan koreksi; tanpa opsi ini hanya pemeriksaan}
        {--actor= : Email Finance Manager/Admin untuk jejak audit}
        {--date= : Tanggal koreksi YYYY-MM-DD; default hari ini}';

    protected $description = 'Periksa/koreksi dua pola jurnal Provisi dan HPP lama tanpa menghapus riwayat';

    public function handle(LegacyJournalRepairService $service): int
    {
        if (! $this->option('job') && ! $this->option('document') && ! $this->option('all')) {
            $this->error('Tentukan --job=, --document=, atau --all.');
            return self::FAILURE;
        }
        $date = $this->option('date') ?: today()->toDateString();
        Validator::make(['date' => $date], ['date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today']])->validate();
        $actor = null;
        if ($this->option('apply')) {
            $actor = User::where('email', (string) $this->option('actor'))->first();
            if (! $actor) {
                $this->error('Isi --actor= dengan email Finance Manager/Admin yang terdaftar.');
                return self::FAILURE;
            }
        }
        $jobs = Job::query()
            ->when($this->option('job'), fn ($q, $number) => $q->where('number', $number))
            ->when($this->option('document'), fn ($q, $number) => $q->whereHas('costs', fn ($cost) => $cost->where('number', $number)))
            ->orderBy('id');
        $found = false;
        $failed = false;
        foreach ($jobs->lazyById(100) as $job) {
            $found = true;
            try {
                $plan = $service->inspect($job);
                $this->line($job->number.' | Provisi '.$plan['provision_before'].' | HPP Unclosed '.$plan['hpp_unclosed_before']);
                if ($plan['warnings']) {
                    $this->warn(implode(' ', $plan['warnings']));
                    $failed = true;
                    continue;
                }
                if (! $plan['reverse_ids'] && ! $plan['entries']) {
                    $this->line('Tidak ada koreksi untuk dua pola yang diperiksa.');
                    continue;
                }
                $this->line('Balik jurnal: '.implode(', ', $plan['reverse_numbers']));
                foreach ($plan['entries'] as $entry) {
                    $this->line($entry['description'].' | Debit '.$entry['debit'].' | Kredit '.$entry['credit']);
                }
                if ($this->option('apply')) {
                    $result = $service->repair($job, $actor, $date);
                    $this->info('Koreksi: '.implode(', ', $result['created_journals']));
                }
            } catch (\Throwable $error) {
                $this->error($job->number.': '.$error->getMessage());
                $failed = true;
            }
        }
        if (! $found) {
            $this->error('Job/dokumen tidak ditemukan.');
            return self::FAILURE;
        }
        if (! $this->option('apply')) {
            $this->info('Pemeriksaan selesai. Tidak ada jurnal yang diubah.');
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
