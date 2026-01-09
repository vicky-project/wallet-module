<?php

return [
	"name" => "Wallet",
	"default_currency" => "IDR",
	"back_to_server_url" => env("WALLET_SERVER_URL", config("app.url", null)),
];
