<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubscriptionNotification extends Notification
{
    use Queueable;

    /**
     * @param  'expiring'|'grace_reminder'|'grace_started'|'downgraded'|'canceled'|'payment_failed'  $event
     */
    public function __construct(
        public Subscription $subscription,
        public string $event,
        public int $daysLeft = 0,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => match ($this->event) {
                'expiring' => 'subscription_expiring',
                'grace_reminder' => 'subscription_grace_reminder',
                'grace_started' => 'subscription_grace_started',
                'downgraded' => 'subscription_downgraded',
                'canceled' => 'subscription_canceled',
                'payment_failed' => 'subscription_past_due',
            },
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan->name,
            'days_left' => $this->daysLeft,
            'message' => match ($this->event) {
                'expiring' => "Votre abonnement {$this->subscription->plan->name} se termine dans {$this->daysLeft} jours. Renouvelez-le pour ne pas perdre vos avantages.",
                'grace_reminder' => "Votre abonnement {$this->subscription->plan->name} se termine dans {$this->daysLeft} jours. Renouvelez-le avant la fin pour garder vos avantages.",
                'grace_started' => "Votre abonnement {$this->subscription->plan->name} est arrivé à échéance. Vous gardez vos avantages 7 jours — renouvelez avant la fin pour éviter le retour au plan Free.",
                'downgraded' => "Votre abonnement {$this->subscription->plan->name} n'a pas été renouvelé : vous êtes repassé au plan Free.",
                'canceled' => "Votre abonnement {$this->subscription->plan->name} a été annulé. Vous pouvez vous réabonner à tout moment depuis la page Tarifs.",
                'payment_failed' => "Le paiement de votre abonnement {$this->subscription->plan->name} a échoué. Mettez à jour votre moyen de paiement depuis la page Abonnement pour conserver vos avantages.",
            },
            'url' => '/settings/subscription',
        ];
    }
}
