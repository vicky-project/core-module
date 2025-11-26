<?php

return [
	"name" => "Core",
	"cache_key_prefix" => "laravel_module",
	"vendor_name" => "vicky-project",
	"notification" => [
		"channels" => ["log", "database", "email"], // log, database, email, slack, sms
		"admin_emails" => ["admin@example.com"],
		"system_admin" => "system@example.com",
		"emergency_contacts" => ["emergency@example.com"],

		"slack" => [
			"webhook_url" => env("MODULE_SLACK_WEBHOOK_URL"),
		],

		"sms" => [
			"phone_numbers" => ["+1234567890"],
		],
	],

	"backup" => [
		"retention_days" => 30,
		"disk" => "local",
	],
];
