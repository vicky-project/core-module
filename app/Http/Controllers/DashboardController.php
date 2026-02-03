<?php

namespace Modules\Core\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
	public function index()
	{
		return view("core::dashboard.index", [
			"title" => "Dashboard",
			"breadcrumbs" => [["name" => "Dashboard", "active" => true]],
		]);
	}

	public function telegramCallback(Request $request)
	{
		try {
			$auth_data = $this->checkTelegramAuthorization($request->all());

			$user = User::mergeFillable([
				"telegram_id",
				"telegram_username",
				"auth_date",
			])->firstOrCreate(
				["telegram_id" => $auth_data["id"]],
				[
					"email" => $auth_data["username"] . "@telegram.com",
					"name" =>
						$auth_data["first_name"] .
						(isset($auth_data["last_name"])
							? " " . $auth_data["last_name"]
							: ""),
					"telegram_username" => $auth_data["username"],
					"auth_date" => $auth_data["auth_date"],
				]
			);

			if ($user) {
				\Auth::loginUsingId($user->id);

				return redirect()->route("cores.dashboard");
			}

			return redirect()
				->route("register")
				->withErrors(
					"User not found and we can not create new user for you. Please create user manual."
				);
		} catch (\Exception $e) {
			\Log::error("Failed to login using telegram", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return redirect()
				->route("login")
				->withErrors($e->getMessage());
		}
	}

	private function checkTelegramAuthorization($auth_data)
	{
		\Log::info("Get data from telegram.", ["data" => $auth_data]);
		$tele_data = collect($auth_data)
			->only(["id", "first_name", "last_name", "auth_date", "hash"])
			->toArray();

		$bot_token = config("core.telegram.token");
		$check_hash = $tele_data["hash"];
		unset($tele_data["hash"]);
		$data_check_arr = [];
		foreach ($tele_data as $key => $value) {
			$data_check_arr[] = $key . "=" . $value;
		}
		sort($data_check_arr);
		$data_check_string = implode("\n", $data_check_arr);
		$secret_key = hash("sha256", $bot_token, true);
		$hash = hash_hmac("sha256", $data_check_string, $secret_key);

		dd(
			$auth_data,
			$tele_data,
			$data_check_arr,
			$data_check_string,
			$hash,
			$check_hash,
			$secret_key,
			strcmp($hash, $check_hash)
		);
		if (strcmp($hash, $check_hash) !== 0) {
			throw new \Exception("Data is NOT from Telegram");
		}
		if (time() - $tele_data["auth_date"] > 86400) {
			throw new \Exception("Data is outdated");
		}
		return $tele_data;
	}
}
