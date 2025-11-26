<?php

namespace Modules\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ModuleInstallationFailed extends Notification implements ShouldQueue
{
	use Queueable;

	protected $data;

	public function __construct($data)
	{
		$this->data = $data;
	}

	public function via($notifiable)
	{
		return ["database", "mail"];
	}

	public function toDatabase($notifiable)
	{
		return [
			"type" => "module_installation_failed",
			"package_name" => $this->data["package_name"],
			"version" => $this->data["version"],
			"error" => $this->data["error"],
			"backup_path" => $this->data["backup_path"],
			"failed_at" => $this->data["failed_at"],
			"troubleshooting" => $this->data["troubleshooting"],
			"message" => "Module installation failed: {$this->data["package_name"]} - {$this->data["error"]}",
		];
	}

	public function toMail($notifiable)
	{
		$mail = (new MailMessage())
			->subject("Module Installation Failed - " . config("app.name"))
			->greeting("Attention Required!")
			->line("A module installation has failed and required attention.")
			->line("**Package:** " . $this->data["package_name"])
			->line("**Version:** " . $this->data["version"])
			->line("**Error:** " . $this->data["error"])
			->line(
				"**Backup:** " .
					($this->data["backup_path"] ? "Available" : "Not Available")
			)
			->line(
				"**Failed At:** " . $this->data["failed_at"]->format("Y-m-d H:i:s")
			);

		if (!empty($this->data["troubleshooting"])) {
			$mail->line("**Troubleshooting Tips:**");
			foreach ($this->data["troubleshooting"] as $tip) {
				$mail->line("- " . $tip);
			}
		}

		return $mail->error();
	}
}
