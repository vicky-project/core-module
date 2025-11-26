<?php

namespace Modules\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ModuleInstallationSuccess extends Notification implements ShouldQueue
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
			"type" => "module_installation_success",
			"package_name" => $this->data["package_name"],
			"module_name" => $this->data["module_name"],
			"version" => $this->data["version"],
			"installed_at" => $this->data["installed_at"],
			"server" => $this->data["server"]["name"],
			"message" => "Module {$this->data["module_name"]} v{$this->data["version"]} installed successfully",
		];
	}

	public function toMail($notifiable)
	{
		return (new MailMessage())
			->subject("Module Installed Successfully - " . config("app.name"))
			->greeting("Hello " . $notifiable->name . "!")
			->line("A module has been successfully installed on your application.")
			->line("**Module:** " . $this->data["module_name"])
			->line("**Version:** " . $this->data["version"])
			->line("**Package:** " . $this->data["package_name"])
			->line("**Server:** " . $this->data["server"]["name"])
			->line(
				"**Installed At:** " .
					$this->data["installed_at"]->format("Y-m-d H:i:s")
			)
			->action("View Modules", route("cores.index"))
			->success();
	}
}
