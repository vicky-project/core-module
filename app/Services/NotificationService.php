<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Nwidart\Modules\Facades\Module;
use Modules\Core\Notifications\ModuleInstallationSuccess;
use Modules\Core\Notifications\ModuleInstallationFailed;
use Modules\Core\Notifications\EmergencyFailureNotification;
use Modules\Core\Notifications\ModuleUpdateAvailable;
use Carbon\Carbon;

class NotificationService
{
	protected $channels;
	protected $adminEmails;
	protected $systemAdmin;

	public function __construct()
	{
		$this->channels = config("core.notification.channels", ["log", "database"]);
		$this->adminEmails = config("core.notification.admin_emails", []);
		$this->systemAdmin = config(
			"core.notification.system_admin",
			config("mail.from.address")
		);
	}

	/**
	 * Send success notification after module installation
	 */
	public function sendInstallationSuccessNotification(
		$packageName,
		$version,
		$moduleName = null
	) {
		$moduleName = $moduleName ?: $this->extractModuleName($packageName);

		$data = [
			"package_name" => $packageName,
			"version" => $version,
			"module_name" => $moduleName,
			"installed_at" => Carbon::now(),
			"server" => $this->getServerInfo(),
			"application" => $this->getApplicationInfo(),
		];

		$this->sendNotification("installation_success", $data);
	}

	/**
	 * Send failure notification when module installation fails
	 */
	public function sendInstallationFailureNotification(
		$packageName,
		$version,
		$error,
		$backupPath = null
	) {
		$data = [
			"package_name" => $packageName,
			"version" => $version,
			"error" => $error,
			"backup_path" => $backupPath,
			"failed_at" => Carbon::now(),
			"server" => $this->getServerInfo(),
			"application" => $this->getApplicationInfo(),
			"troubleshooting" => $this->getTroubleshootingTips($error),
		];

		$this->sendNotification("installation_failed", $data);
	}

	/**
	 * Send emergency notification when cleanup also fails
	 */
	public function sendEmergencyFailureNotification(
		$packageName,
		$version,
		$installationError,
		$cleanupError
	) {
		$data = [
			"package_name" => $packageName,
			"version" => $version,
			"installation_error" => $installationError,
			"cleanup_error" => $cleanupError,
			"occurred_at" => Carbon::now(),
			"server" => $this->getServerInfo(),
			"application" => $this->getApplicationInfo(),
			"emergency_actions" => $this->getEmergencyActions(),
		];

		$this->sendNotification("emergency_failure", $data);
	}

	/**
	 * Send notification when module update is available
	 */
	public function sendUpdateAvailableNotification(
		$moduleName,
		$currentVersion,
		$availableVersion
	) {
		$data = [
			"module_name" => $moduleName,
			"current_version" => $currentVersion,
			"available_version" => $availableVersion,
			"checked_at" => Carbon::now(),
			"update_url" => $this->getUpdateUrl($moduleName),
			"changelog" => $this->getChangelog($moduleName, $availableVersion),
		];

		$this->sendNotification("update_available", $data);
	}

	/**
	 * Send backup created notification
	 */
	public function sendBackupCreatedNotification(
		$backupPath,
		$backupSize,
		$moduleName = null
	) {
		$data = [
			"backup_path" => $backupPath,
			"backup_size" => $backupSize,
			"backup_size_formatted" => $this->formatBytes($backupSize),
			"created_at" => Carbon::now(),
			"module_name" => $moduleName,
			"retention_period" => config("modules.backup.retention_days", 30),
		];

		$this->sendNotification("backup_created", $data);
	}

	/**
	 * Send backup restored notification
	 */
	public function sendBackupRestoredNotification($backupPath, $reason)
	{
		$data = [
			"backup_path" => $backupPath,
			"restored_at" => Carbon::now(),
			"reason" => $reason,
			"server" => $this->getServerInfo(),
			"application" => $this->getApplicationInfo(),
		];

		$this->sendNotification("backup_restored", $data);
	}

	/**
	 * Send system health notification
	 */
	public function sendSystemHealthNotification($healthStatus, $issues = [])
	{
		$data = [
			"health_status" => $healthStatus,
			"checked_at" => Carbon::now(),
			"issues" => $issues,
			"server" => $this->getServerInfo(),
			"application" => $this->getApplicationInfo(),
			"modules" => $this->getModulesStatus(),
		];

		$this->sendNotification("system_health", $data);
	}

	/**
	 * Main notification dispatcher
	 */
	protected function sendNotification($type, $data)
	{
		foreach ($this->channels as $channel) {
			try {
				$method = "sendVia" . ucfirst($channel);
				if (method_exists($this, $method)) {
					$this->{$method}($type, $data);
				}
			} catch (\Exception $e) {
				Log::error(
					"Failed to send notification via {$channel} for {$type}: " .
						$e->getMessage()
				);
			}
		}
	}

	/**
	 * Send notification via email
	 */
	protected function sendViaEmail($type, $data)
	{
		$recipients = $this->getRecipients($type);

		if (empty($recipients)) {
			return;
		}

		$subject = $this->getEmailSubject($type, $data);
		$view = $this->getEmailView($type);

		foreach ($recipients as $recipient) {
			Mail::send($view, $data, function ($message) use (
				$recipient,
				$subject,
				$type
			) {
				$message
					->to($recipient)
					->subject($subject)
					->from(config("mail.from.address"), config("mail.from.name"));

				// Add priority for failure notifications
				if (in_array($type, ["installation_failed", "emergency_failure"])) {
					$message->priority(1);
				}
			});
		}

		Log::info(
			"Email notification sent for {$type} to " .
				count($recipients) .
				" recipients"
		);
	}

	/**
	 * Send notification via database (store in notifications table)
	 */
	protected function sendViaDatabase($type, $data)
	{
		// This requires the Notifiable trait on your User model
		$notificationClass = $this->getDatabaseNotificationClass($type);

		if (!$notificationClass) {
			return;
		}

		$users = $this->getNotifiableUsers();

		Notification::send($users, new $notificationClass($data));

		Log::info("Database notification stored for {$type}");
	}

	/**
	 * Send notification via Slack
	 */
	protected function sendViaSlack($type, $data)
	{
		if (!config("modules.notification.slack.webhook_url")) {
			return;
		}

		$slackMessage = $this->formatSlackMessage($type, $data);

		// Using Laravel's HTTP client to send to Slack webhook
		$httpClient = new \GuzzleHttp\Client();

		$httpClient->post(config("modules.notification.slack.webhook_url"), [
			"json" => $slackMessage,
		]);

		Log::info("Slack notification sent for {$type}");
	}

	/**
	 * Send notification via log file
	 */
	protected function sendViaLog($type, $data)
	{
		$logMessage = $this->formatLogMessage($type, $data);

		switch ($type) {
			case "installation_failed":
			case "emergency_failure":
				Log::error($logMessage);
				break;
			case "installation_success":
				Log::info($logMessage);
				break;
			default:
				Log::notice($logMessage);
				break;
		}
	}

	/**
	 * Send notification via SMS (using Nexmo, Twilio, etc.)
	 */
	protected function sendViaSms($type, $data)
	{
		// Only send SMS for critical failures
		if (!in_array($type, ["emergency_failure", "installation_failed"])) {
			return;
		}

		$phoneNumbers = config("modules.notification.sms.phone_numbers", []);
		$message = $this->formatSmsMessage($type, $data);

		foreach ($phoneNumbers as $phoneNumber) {
			// Implementation depends on your SMS provider
			// Example with Twilio:
			/*
            $twilio = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );
            
            $twilio->messages->create(
                $phoneNumber,
                [
                    'from' => config('services.twilio.from'),
                    'body' => $message
                ]
            );
            */
		}

		Log::info("SMS notification sent for {$type}");
	}

	/**
	 * Format Slack message
	 */
	protected function formatSlackMessage($type, $data)
	{
		$baseMessage = [
			"username" => config("app.name") . " Module Manager",
			"icon_emoji" => $this->getSlackIcon($type),
		];

		switch ($type) {
			case "installation_success":
				$baseMessage["attachments"] = [
					[
						"color" => "good",
						"title" => "Module Installed Successfully",
						"fields" => [
							[
								"title" => "Module",
								"value" => $data["module_name"],
								"short" => true,
							],
							[
								"title" => "Version",
								"value" => $data["version"],
								"short" => true,
							],
							[
								"title" => "Package",
								"value" => $data["package_name"],
								"short" => false,
							],
							[
								"title" => "Server",
								"value" => $data["server"]["name"],
								"short" => true,
							],
						],
						"ts" => Carbon::now()->timestamp,
					],
				];
				break;

			case "installation_failed":
				$baseMessage["attachments"] = [
					[
						"color" => "danger",
						"title" => "Module Installation Failed",
						"fields" => [
							[
								"title" => "Package",
								"value" => $data["package_name"],
								"short" => true,
							],
							[
								"title" => "Version",
								"value" => $data["version"],
								"short" => true,
							],
							["title" => "Error", "value" => $data["error"], "short" => false],
							[
								"title" => "Backup",
								"value" => $data["backup_path"] ? "Available" : "Not Available",
								"short" => true,
							],
						],
						"ts" => Carbon::now()->timestamp,
					],
				];
				break;

			case "emergency_failure":
				$baseMessage["attachments"] = [
					[
						"color" => "danger",
						"title" => "EMERGENCY: Module Installation Cleanup Failed",
						"fields" => [
							[
								"title" => "Package",
								"value" => $data["package_name"],
								"short" => true,
							],
							[
								"title" => "Installation Error",
								"value" => substr($data["installation_error"], 0, 100) . "...",
								"short" => false,
							],
							[
								"title" => "Cleanup Error",
								"value" => substr($data["cleanup_error"], 0, 100) . "...",
								"short" => false,
							],
						],
						"ts" => Carbon::now()->timestamp,
					],
				];
				break;
		}

		return $baseMessage;
	}

	/**
	 * Format log message
	 */
	protected function formatLogMessage($type, $data)
	{
		switch ($type) {
			case "installation_success":
				return "Module installed successfully: {$data["package_name"]}@{$data["version"]} on {$data["server"]["name"]}";

			case "installation_failed":
				return "Module installation failed: {$data["package_name"]}@{$data["version"]} - {$data["error"]}";

			case "emergency_failure":
				return "EMERGENCY: Module installation and cleanup failed: {$data["package_name"]} - Installation: {$data["installation_error"]} - Cleanup: {$data["cleanup_error"]}";

			case "backup_created":
				return "Backup created: {$data["backup_path"]} ({$data["backup_size_formatted"]})" .
					($data["module_name"] ? " for module {$data["module_name"]}" : "");

			case "backup_restored":
				return "Backup restored: {$data["backup_path"]} - Reason: {$data["reason"]}";

			default:
				return "Notification: {$type} - " . json_encode($data);
		}
	}

	/**
	 * Format SMS message
	 */
	protected function formatSmsMessage($type, $data)
	{
		$appName = config("app.name");

		switch ($type) {
			case "emergency_failure":
				return "🚨 {$appName}: EMERGENCY - Module install failed for {$data["package_name"]}. Manual intervention required.";

			case "installation_failed":
				return "⚠️ {$appName}: Module install failed for {$data["package_name"]}. Backup restored.";

			default:
				return "{$appName}: {$type} - {$data["package_name"]}";
		}
	}

	/**
	 * Get email subject
	 */
	protected function getEmailSubject($type, $data)
	{
		$appName = config("app.name");

		$subjects = [
			"installation_success" => "✅ Module Installed Successfully - {$appName}",
			"installation_failed" => "❌ Module Installation Failed - {$appName}",
			"emergency_failure" => "🚨 EMERGENCY: Module Installation Failed - {$appName}",
			"update_available" => "📦 Module Update Available - {$appName}",
			"backup_created" => "💾 Backup Created - {$appName}",
			"backup_restored" => "🔙 Backup Restored - {$appName}",
			"system_health" => "🏥 System Health Report - {$appName}",
		];

		return $subjects[$type] ?? "Notification - {$appName}";
	}

	/**
	 * Get email view
	 */
	protected function getEmailView($type)
	{
		$views = [
			"installation_success" => "core::emails.module_installation_success",
			"installation_failed" => "core::emails.module_installation_failed",
			"emergency_failure" => "core::emails.emergency_failure",
			"update_available" => "core::emails.module_update_available",
			"backup_created" => "core::emails.backup_created",
			"backup_restored" => "core::emails.backup_restored",
			"system_health" => "core::emails.system_health",
		];

		return $views[$type] ?? "core::emails.default";
	}

	/**
	 * Get database notification class
	 */
	protected function getDatabaseNotificationClass($type)
	{
		$classes = [
			"installation_success" => ModuleInstallationSuccess::class,
			"installation_failed" => ModuleInstallationFailed::class,
			"emergency_failure" => EmergencyFailureNotification::class,
			"update_available" => ModuleUpdateAvailable::class,
		];

		return $classes[$type] ?? null;
	}

	/**
	 * Get notification recipients based on type
	 */
	protected function getRecipients($type)
	{
		$recipients = $this->adminEmails;

		// Add system admin if not already in list
		if (!in_array($this->systemAdmin, $recipients)) {
			$recipients[] = $this->systemAdmin;
		}

		// For emergency failures, also include additional contacts
		if ($type === "emergency_failure") {
			$emergencyContacts = config(
				"modules.notification.emergency_contacts",
				[]
			);
			$recipients = array_merge($recipients, $emergencyContacts);
		}

		return array_unique($recipients);
	}

	/**
	 * Get users who should receive database notifications
	 */
	protected function getNotifiableUsers()
	{
		// This depends on your User model and how you determine who should get notifications
		// Example: return User::where('role', 'admin')->get();

		// For now, return empty collection - implement based on your user management
		return collect([]);
	}

	/**
	 * Get server information
	 */
	protected function getServerInfo()
	{
		return [
			"name" => gethostname(),
			"php_version" => PHP_VERSION,
			"laravel_version" => app()->version(),
			"memory_usage" => $this->formatBytes(memory_get_usage(true)),
			"peak_memory_usage" => $this->formatBytes(memory_get_peak_usage(true)),
			"disk_free_space" => $this->formatBytes(disk_free_space(base_path())),
			"server_software" => $_SERVER["SERVER_SOFTWARE"] ?? "Unknown",
		];
	}

	/**
	 * Get application information
	 */
	protected function getApplicationInfo()
	{
		return [
			"name" => config("app.name"),
			"env" => config("app.env"),
			"url" => config("app.url"),
			"timezone" => config("app.timezone"),
			"maintenance_mode" => app()->isDownForMaintenance(),
		];
	}

	/**
	 * Get modules status
	 */
	protected function getModulesStatus()
	{
		$modules = Module::all();
		$status = [];

		foreach ($modules as $module) {
			$status[] = [
				"name" => $module->getName(),
				"enabled" => $module->isEnabled(),
				"version" => $module->get("version", "1.0.0"),
			];
		}

		return $status;
	}

	/**
	 * Extract module name from package name
	 */
	protected function extractModuleName($packageName)
	{
		$parts = explode("/", $packageName);
		$moduleName = end($parts);
		return str_replace(" ", "", ucwords(str_replace("-", " ", $moduleName)));
	}

	/**
	 * Format bytes to human readable format
	 */
	protected function formatBytes($bytes, $precision = 2)
	{
		$units = ["B", "KB", "MB", "GB", "TB"];

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . " " . $units[$pow];
	}

	/**
	 * Get Slack icon for notification type
	 */
	protected function getSlackIcon($type)
	{
		$icons = [
			"installation_success" => ":white_check_mark:",
			"installation_failed" => ":x:",
			"emergency_failure" => ":rotating_light:",
			"update_available" => ":package:",
			"backup_created" => ":floppy_disk:",
			"backup_restored" => ":leftwards_arrow_with_hook:",
			"system_health" => ":hospital:",
		];

		return $icons[$type] ?? ":bell:";
	}

	/**
	 * Get troubleshooting tips based on error
	 */
	protected function getTroubleshootingTips($error)
	{
		$tips = [];

		if (str_contains($error, "memory")) {
			$tips[] = "Increase PHP memory limit in php.ini";
			$tips[] = "Check available server memory";
		}

		if (
			str_contains($error, "disk space") ||
			str_contains($error, "disk_space")
		) {
			$tips[] = "Free up disk space on the server";
			$tips[] = "Check and clean up temporary files";
		}

		if (
			str_contains($error, "composer") ||
			str_contains($error, "package not found")
		) {
			$tips[] = "Check package name and version on Packagist";
			$tips[] = "Verify composer.json is valid";
			$tips[] = "Run composer clear-cache and try again";
		}

		if (
			str_contains($error, "permission") ||
			str_contains($error, "access denied")
		) {
			$tips[] = "Check file and directory permissions";
			$tips[] = "Verify web server user has write access";
		}

		if (str_contains($error, "database") || str_contains($error, "SQL")) {
			$tips[] = "Check database connection and credentials";
			$tips[] = "Verify database user has required permissions";
			$tips[] = "Check database version compatibility";
		}

		// Default tips
		if (empty($tips)) {
			$tips = [
				"Check application logs for detailed error information",
				"Verify server meets all system requirements",
				"Ensure all dependencies are properly installed",
				"Contact system administrator if issue persists",
			];
		}

		return $tips;
	}

	/**
	 * Get emergency actions for critical failures
	 */
	protected function getEmergencyActions()
	{
		return [
			"Check server error logs immediately",
			"Verify application backups are current",
			"Check disk space and memory usage",
			"Review recent system changes",
			"Contact system administrator for immediate assistance",
			"Consider restoring from backup if system is unstable",
		];
	}

	/**
	 * Get update URL for module
	 */
	protected function getUpdateUrl($moduleName)
	{
		// This would typically point to your module repository or update server
		return config("app.url") . "/admin/modules/{$moduleName}/update";
	}

	/**
	 * Get changelog for module update
	 */
	protected function getChangelog($moduleName, $version)
	{
		// This would typically fetch from your module's changelog file
		// For now, return a placeholder
		return "Update to version {$version} includes bug fixes and performance improvements.";
	}

	/**
	 * Test notification configuration
	 */
	public function testNotification($channel = null)
	{
		$testData = [
			"package_name" => "vendor/test-module",
			"version" => "1.0.0",
			"module_name" => "TestModule",
			"test" => true,
			"server" => $this->getServerInfo(),
			"application" => $this->getApplicationInfo(),
		];

		if ($channel) {
			$this->{"sendVia" . ucfirst($channel)}("installation_success", $testData);
		} else {
			$this->sendNotification("installation_success", $testData);
		}

		return true;
	}
}
