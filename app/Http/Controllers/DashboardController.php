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
			$auth_data = $this->checkTelegramAuthorization(
				$request->only([
					"id",
					"first_name",
					"last_name",
					"username",
					"auth_date",
					"hash",
				])
			);

			$user = User::where("telegram_id", $auth_data["id"])->first();

			if (!$user || $user->isEmpty()) {
				if ($request->routeIs("register")) {
					$user = User::create([
						"telegram_id" => $auth_data["id"],
						"email" => $auth_data["username"] . "@telegram.com",
						"name" =>
							$auth_data["first_name"] .
							(isset($auth_data["last_name"])
								? " " . $auth_data["last_name"]
								: ""),
						"telegram_username" => $auth_data["username"],
						"auth_date" => $auth_data["auth_date"],
					]);
				} else {
					return redirect()
						->route("login")
						->withErrors(
							"User not found or user not connected to telegram yet. Please register using telegram"
						);
				}
			}

			if ($user) {
				\Auth::loginUsingId($user->id);

				return redirect()->route("cores.dashboard");
			}

			return redirect()
				->route("register")
				->withErrors(
					"Can not login or register using telegram. Please create user manual or login with another credential."
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

		$bot_token = config("core.telegram.token");
		$check_hash = $auth_data["hash"];
		unset($auth_data["hash"]);
		$data_check_arr = [];
		foreach ($auth_data as $key => $value) {
			$data_check_arr[] = $key . "=" . $value;
		}
		sort($data_check_arr);
		$data_check_string = implode("\n", $data_check_arr);
		$secret_key = hash("sha256", $bot_token, true);
		$hash = hash_hmac("sha256", $data_check_string, $secret_key);

		if (strcmp($hash, $check_hash) !== 0) {
			throw new \Exception("Data is NOT from Telegram");
		}
		if (time() - $auth_data["auth_date"] > 86400) {
			throw new \Exception("Data is outdated");
		}
		return $auth_data;
	}
}
