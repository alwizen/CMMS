<?php

namespace App\Notifications;

use App\Filament\Resources\HousekeepingPlans\HousekeepingPlanResource;
use App\Models\HousekeepingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi ke SPV GOS bahwa pekerjaan pada Plan Harian sudah selesai
 * dan menunggu verifikasi sebelum laporan harian bisa digenerate.
 */
class HousekeepingPlanPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public HousekeepingPlan $plan) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->plan->loadMissing('area');

        return (new MailMessage)
            ->subject('Pekerjaan selesai, menunggu verifikasi SPV GOS: '.$plan->plan_number)
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Pekerjaan pada Plan Harian area '.($plan->area?->name ?? '-').' sudah selesai dikerjakan patra-jasa.')
            ->line('Tanggal plan: '.($plan->plan_date?->format('d M Y') ?? '-'))
            ->line('Silakan periksa hasil pekerjaan (checklist & foto), lalu approve agar laporan harian bisa digenerate.')
            ->action('Buka Plan Harian', HousekeepingPlanResource::getUrl('view', ['record' => $plan]));
    }

    public function toArray(object $notifiable): array
    {
        $plan = $this->plan;

        return [
            'format' => 'filament',
            'duration' => 'persistent',
            'type' => 'housekeeping_plan_pending',
            'plan_id' => $plan->id,
            'plan_number' => $plan->plan_number,
            'plan_date' => $plan->plan_date?->toDateString(),
            'area_id' => $plan->housekeeping_area_id,
            'title' => 'Pekerjaan Selesai, Menunggu Verifikasi',
            'message' => sprintf(
                'Pekerjaan %s (%s) menunggu verifikasi SPV GOS.',
                $plan->plan_number ?? "#{$plan->id}",
                $plan->area?->name ?? '-',
            ),
            'body' => sprintf(
                '%s • %s',
                $plan->plan_number ?? "#{$plan->id}",
                $plan->plan_date?->format('d M Y') ?? '-',
            ),
        ];
    }
}
