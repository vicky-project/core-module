<?php

namespace Modules\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class EmergencyFailureNotification extends Notification implements ShouldQueue
{
	use Queueable;

	protected $data;

	public function __construct($data)
	{
		$this->data = $data;
	}

	public function via($notifiable)
	{
		return ["database", "mail"]; // Add 'sms' if configured
	}

	public function toDatabase($notifiable)
	{
		return [
			"type" => "emergency_failure",
			"package_name" => $this->data["package_name"],
			"version" => $this->data["version"],
			"installation_error" => $this->data["installation_error"],
			"cleanup_error" => $this->data["cleanup_error"],
			"occurred_at" => $this->data["occurred_at"],
			"emergency_actions" => $this->data["emergency_actions"],
			"message" => "EMERGENCY: Module installation and cleanup failed for {$this->data["package_name"]}",
		];
	}

	public function toMail($notifiable)
	{
		$mail = (new MailMessage())
			->subject(
				"🚨 EMERGENCY: Module Installation Failure - " . config("app.name")
			)
			->greeting("🚨 EMERGENCY NOTIFICATION!")
			->line(
				"A critical failure has occurred during module installation and cleanup."
			)
			->line("**Package:** " . $this->data["package_name"])
			->line("**Version:** " . $this->data["version"])
			->line("**Installation Error:** " . $this->data["installation_error"])
			->line("**Cleanup Error:** " . $this->data["cleanup_error"])
			->line(
				"**Occurred At:** " . $this->data["occurred_at"]->format("Y-m-d H:i:s")
			)
			->line("**IMMEDIATE ACTION REQUIRED:**");

		foreach ($this->data["emergency_actions"] as $action) {
			$mail->line("- " . $action);
		}

		return $mail->error();
	}
}
