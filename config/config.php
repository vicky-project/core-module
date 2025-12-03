<?php

return [
	"name" => "Core",
	"cache_key_prefix" => "laravel_module",
	"vendor_name" => "vicky-project",
	"notification" => [
		"channels" => ["log", "database"], // log, database, email, slack, sms
		"admin_emails" => ["admin@example.com"],
		"system_admin" => "system@example.com",
		"emergency_contacts" => ["emergency@example.com"],

		"slack" => [
			"webhook_url" => env("MODULE_SLACK_WEBHOOK_URL"),
		],

		"sms" => [
			"phone_numbers" => ["+1234567890"],
		],
		/*
		| User who get notification.
		| May be ID, name, email, or role
		| default to first user ID.
		|*/
		"users" => [1],
	],

	"backup" => [
		"retention_days" => 30,
		"disk" => "local",
	],

	/*
	| ========================================
	| Server Monitoring Config
	| ========================================
	|
	*/
	"monitors" => [
		"show" => [
			"temps" => true,
			"load" => true,
			"ip" => true,
			"kernel" => true,
			"os" => true,
			"ram" => true,
			"hd" => true,
			"webservice" => true,
			"phpversion" => true,
			"network" => true,
			"uptime" => true,
			"cpu" => true,
			"process_stats" => true,
			"hostname" => true,
			"distro" => true,
			"model" => true,
			"services" => true,
			"raid" => true,
		],
		"cpu_usage" => true,
		"temps" => [
			"hwmon" => true,
			"thermal_zone" => true,
			"hddtemp" => true,
			"mbmon" => true,
			"sensord" => true,
		],
		"raid" => ["gmirror" => true, "mdadm" => true],
		"temps_show0rpmfans" => true,
		"show_errors" => false,
		"hddtemp" => [
			"mode" => "daemon", // daemon or syslog
		],
		"services" => [
			"pidFiles" => ["SSHd" => "/var/run/sshd.pid"],
			"executables" => ["MySQLd" => "/usr/sbin/nysqld"],
		],
	],
];
