<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

use App\Ban;
use App\Http\Controllers\AdvancedSearchController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\CasinoLogController;
use App\Http\Controllers\ContainerController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\GraphController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LoadingScreenController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\OverwatchController;
use App\Http\Controllers\PanelLogController;
use App\Http\Controllers\PlayerBanController;
use App\Http\Controllers\PlayerCharacterController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PlayerRouteController;
use App\Http\Controllers\PlayerDataController;
use App\Http\Controllers\PlayerWarningController;
use App\Http\Controllers\AntiCheatController;
use App\Http\Controllers\StocksController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffChatController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SuspiciousController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TwitterController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\ToolController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

// Authentication methods.
Route::group(['prefix' => 'auth'], function () {
    Route::group(['middleware' => ['session']], function () {
        Route::get('/login', [DiscordController::class, 'login']);
        Route::get('/complete', [DiscordController::class, 'complete']);
    });

    Route::get('/redirect', [DiscordController::class, 'redirect']);
});

// Logging in and out.
Route::group(['namespace' => 'Auth', 'middleware' => ['session']], function () {
    Route::name('login')->get('/login', [LoginController::class, 'render']);
    Route::name('logout')->post('/logout', [LogoutController::class, 'logout']);
});

// Routes requiring being logged in as a staff member.
Route::group(['middleware' => ['log', 'staff', 'session']], function () {
    // Refresh Discord
    Route::get('/auth/refresh', [DiscordController::class, 'refresh']);

    // Home.
    Route::get('/', [HomeController::class, 'render']);
    Route::post('/announcement', [HomeController::class, 'serverAnnouncement']);

    // Steam Lookup.
    Route::get('/steam', [LookupController::class, 'renderSteam']);
    Route::post('/steam', [LookupController::class, 'playerInfoSteam']);
    Route::get('/discord', [LookupController::class, 'renderDiscord']);
    Route::post('/discord', [LookupController::class, 'playerInfoDiscord']);

    Route::get('/chat', [StaffChatController::class, 'chat']);
    Route::post('/chat', [StaffChatController::class, 'sendChat']);

    Route::get('/staffChat', [StaffChatController::class, 'staffChat']);

    // Players.
    Route::get('/players', [PlayerController::class, 'index']);
    Route::get('/players/{player}', [PlayerController::class, 'show']);
    Route::resource('players.characters', PlayerCharacterController::class);
    Route::resource('players.bans', PlayerBanController::class);

    // Player warnings.
    Route::resource('players.warnings', PlayerWarningController::class);
    Route::post('/players/{player}/warnings/{warning}/refresh', [PlayerWarningController::class, 'refresh']);
    Route::post('/players/{player}/warnings/bulk', [PlayerWarningController::class, 'bulkDeleteWarnings']);
    Route::post('/players/{player}/warnings/{warning}/react', [PlayerWarningController::class, 'react']);
    Route::get('/players/{player}/warnings/{warning}/react', [PlayerWarningController::class, 'reactions']);

    // Player information.
    Route::get('/players/{player}/statistics/{source}', [PlayerController::class, 'statistics']);
    Route::get('/players/{player}/linked', [PlayerRouteController::class, 'linkedAccounts']);
    Route::get('/players/{player}/linked_hwid', [PlayerRouteController::class, 'linkedHWID']);
    Route::get('/players/{player}/discord', [PlayerRouteController::class, 'discordAccounts']);
    Route::get('/players/{player}/antiCheat', [PlayerRouteController::class, 'antiCheat']);
    Route::get('/players/{player}/ip', [PlayerRouteController::class, 'playerIPInfo']);
    Route::get('/players/{player}/ban', [PlayerRouteController::class, 'ban']);
    Route::get('/players/{player}/bans/{ban}/system', [PlayerBanController::class, 'systemInfo']);

    // Player actions.
    Route::post('/players/{player}/kick', [PlayerRouteController::class, 'kick']);
    Route::post('/players/{player}/staffPM', [PlayerRouteController::class, 'staffPM']);
    Route::post('/players/{player}/unloadCharacter', [PlayerRouteController::class, 'unloadCharacter']);
    Route::post('/players/{player}/revivePlayer', [PlayerRouteController::class, 'revivePlayer']);

    // Update player data.
    Route::post('/players/{player}/updateEnabledCommands', [PlayerDataController::class, 'updateEnabledCommands']);
    Route::post('/players/{player}/updateBanExceptionStatus', [PlayerDataController::class, 'updateBanExceptionStatus']);
    Route::post('/players/{player}/updateWhitelistStatus', [PlayerDataController::class, 'updateWhitelistStatus']);
    Route::post('/players/{player}/updateMuteStatus', [PlayerDataController::class, 'updateMuteStatus']);
    Route::post('/players/{player}/updateTag', [PlayerDataController::class, 'updateTag']);

    // Ban actions.
    Route::get('/smurf/{hash}', [PlayerBanController::class, 'smurfBan']);
    Route::post('/players/{player}/unlink/{player2}', [PlayerBanController::class, 'unlinkIdentifiers']);
    Route::post('/players/{player}/unlink_hwid/{player2}', [PlayerBanController::class, 'unlinkHWID']);
    Route::post('/players/{player}/bans/{ban}/lock', [PlayerBanController::class, 'lockBan']);
    Route::post('/players/{player}/bans/{ban}/unlock', [PlayerBanController::class, 'unlockBan']);
    Route::post('/players/{player}/bans/{ban}/schedule', [PlayerBanController::class, 'schedule']);
    Route::post('/players/{player}/bans/{ban}/unschedule', [PlayerBanController::class, 'unschedule']);

    // Unrelated player things.
    Route::get('/new_players', [PlayerController::class, 'newPlayers']);
    Route::get('/backstories', [PlayerCharacterController::class, 'backstories']);
    Route::get('/api/backstories', [PlayerCharacterController::class, 'backstoriesApi']);

    // Bans.
    Route::get('/bans', [PlayerBanController::class, 'index']);
    Route::get('/my_bans', [PlayerBanController::class, 'indexMine']);
    Route::get('/system_bans', [PlayerBanController::class, 'indexSystem']);

    // Ban API.
    Route::get('/findUserBanHash/{hash}', [PlayerBanController::class, 'findUserBanHash']);
    Route::get('/ban_info/{hash}', [PlayerBanController::class, 'banInfo']);

    // Linked accounts.
    Route::get('/linked_ips/{license}', [PlayerBanController::class, 'linkedIPs']);
    Route::get('/linked_tokens/{license}', [PlayerBanController::class, 'linkedTokens']);
    Route::get('/linked_identifiers/{license}', [PlayerBanController::class, 'linkedIdentifiers']);
    Route::get('/linked_devices/{license}', [PlayerBanController::class, 'linkedDevices']);

    // Damage logs.
    Route::get('/damage', [LogController::class, 'damageLogs']);
    Route::get('/who_damaged/{license}', [PlayerRouteController::class, 'whoDamaged']);
    Route::get('/who_was_damaged/{license}', [PlayerRouteController::class, 'whoWasDamagedBy']);

    // Inventories.
    Route::get('/inventory/{inventory}', [InventoryController::class, 'show']);
    Route::get('/inventory/resolve/{inventory}', [InventoryController::class, 'resolve']);
    Route::get('/inventory/trunk/{vehicle}', [InventoryController::class, 'resolveTrunk']);
    Route::get('/inventory/attach_identity/{character}', [InventoryController::class, 'attachIdentity']);
    Route::put('/inventory/{inventory}/items/{slot}', [InventoryController::class, 'update']);
    Route::delete('/inventory/{inventory}/items/{slot}', [InventoryController::class, 'delete']);

    Route::get('/inventory/logs/{inventory}', [InventoryController::class, 'logs']);
    Route::get('/inventory/item/{id}', [InventoryController::class, 'itemHistory']);

    // Advanced search.
    Route::get('/advanced', [AdvancedSearchController::class, 'index']);

    Route::group(['middleware' => ['super-admin']], function () {
        // Blacklisted Identifiers.
        Route::get('/blacklist', [BlacklistController::class, 'index']);
        Route::post('/blacklist', [BlacklistController::class, 'store']);
        Route::delete('/blacklist/{identifier}', [BlacklistController::class, 'destroy']);
        Route::post('/blacklist/import', [BlacklistController::class, 'import']);

        // Loading screen pictures
        Route::get('/loading_screen', [LoadingScreenController::class, 'index']);
        Route::delete('/loading_screen/{id}', [LoadingScreenController::class, 'delete']);
        Route::post('/loading_screen', [LoadingScreenController::class, 'add']);
        Route::put('/loading_screen/{id}', [LoadingScreenController::class, 'edit']);
    });

    // Suspicious.
    Route::get('/suspicious', [SuspiciousController::class, 'index']);

    // Twitter.
    Route::get('twitter', [TwitterController::class, 'index']);
    Route::get('twitter/{user}', [TwitterController::class, 'user']);
    Route::post('twitter/{user}/verify', [TwitterController::class, 'verify']);
    Route::post('tweets/delete', [TwitterController::class, 'deleteTweets']);
    Route::post('tweets/edit/{post}', [TwitterController::class, 'editTweet']);

    // Logs.
    Route::resource('logs', LogController::class);
    Route::get('/moneyLogs', [LogController::class, 'moneyLogs']);
    Route::get('/darkChat', [LogController::class, 'darkChat']);
    Route::get('/searches', [LogController::class, 'searches']);
    Route::get('/screenshot_logs', [LogController::class, 'screenshotLogs']);
    Route::get('/damage', [LogController::class, 'damageLogs']);
    Route::get('/phoneLogs', [LogController::class, 'phoneLogs']);
    Route::get('/phoneLogs/get', [LogController::class, 'phoneLogsData']);

    // Casino Logs.
    Route::resource('casino', CasinoLogController::class);

    // Panel Logs.
    Route::get('/panel', [PanelLogController::class, 'index']);

    // Characters.
    Route::resource('characters', PlayerCharacterController::class);
    Route::post('vehicles/delete/{vehicle}', [PlayerCharacterController::class, 'deleteVehicle']);
    Route::post('vehicles/edit/{vehicle}', [PlayerCharacterController::class, 'editVehicle']);
    Route::post('vehicles/resetGarage/{vehicle}/{fullReset}', [PlayerCharacterController::class, 'resetGarage']);
    Route::post('/players/{player}/characters/{character}/removeTattoos', [PlayerCharacterController::class, 'removeTattoos']);
    Route::post('/players/{player}/characters/{character}/resetSpawn', [PlayerCharacterController::class, 'resetSpawn']);
    Route::put('/players/{player}/characters/{character}/editBalance', [PlayerCharacterController::class, 'editBalance']);
    Route::post('/players/{player}/characters/{character}/addVehicle', [PlayerCharacterController::class, 'addVehicle']);
    Route::post('/players/{player}/characters/{character}/updateLicenses', [PlayerCharacterController::class, 'updateLicenses']);
    Route::post('/players/{player}/characters/{character}/refreshEmail', [PlayerCharacterController::class, 'refreshEmail']);

    // Savings Accounts.
    Route::get('/savings/{id}/logs', [PlayerCharacterController::class, 'savingsLogs']);

    // Stocks Companies (realty).
    Route::get('/stocks/companies', [StocksController::class, 'companies']);
    Route::post('/stocks/property/{propertyId}', [StocksController::class, 'updateProperty']);

    // Storage Containers.
    Route::get('/containers', [ContainerController::class, 'containers']);

    // Map.
    Route::get('/map/{server?}', [MapController::class, 'index']);
    Route::post('/map/playerNames', [MapController::class, 'playerNames']);
    Route::get('/map/noclipBans', [MapController::class, 'noclipBans']);

    // Statistics.
    Route::get('/statistics', [StatisticsController::class, 'render']);
    Route::get('/statistics/economy', [StatisticsController::class, 'economyStatistics']);
    Route::get('/statistics/players', [StatisticsController::class, 'playerStatistics']);
    Route::get('/statistics/fps', [StatisticsController::class, 'fpsStatistics']);
    Route::post('/statistics/money', [StatisticsController::class, 'moneyLogs']);
    Route::get('/statistics/{source}', [StatisticsController::class, 'source']);
    Route::get('/points', [StatisticsController::class, 'points']);
    Route::get('/staff', [StatisticsController::class, 'staffStatistics']);

    // Overwatch.
    Route::get('/overwatch', [OverwatchController::class, 'index']);
    Route::get('/live', [OverwatchController::class, 'live']);
    Route::get('/live/replay/{license}', [OverwatchController::class, 'replay']);
    Route::patch('/live/set/{license}/{source}', [OverwatchController::class, 'setSpectating']);

    // Screenshots.
    Route::get('/anti_cheat', [AntiCheatController::class, 'render']);
    Route::get('/anti_cheat/statistics', [AntiCheatController::class, 'statistics']);

    // Documentations.
    Route::get('/docs/{type}', [DocumentationController::class, 'docs']);

    // Errors.
    Route::get('/errors/client', [ErrorController::class, 'client']);
    Route::get('/errors/server', [ErrorController::class, 'server']);

    // Tokens.
    Route::get('/tokens', [TokenController::class, 'index']);
    Route::get('/tokens/logs', [TokenController::class, 'logs']);
    Route::get('/tokens/rps', [TokenController::class, 'rps']);
    Route::post('/tokens', [TokenController::class, 'create']);
    Route::put('/tokens/{token}', [TokenController::class, 'update']);
    Route::delete('/tokens/{token}', [TokenController::class, 'delete']);

    // Roles.
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{player}', [RoleController::class, 'get']);
    Route::post('/roles/{player}', [RoleController::class, 'update']);

    // Settings.
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::delete('/settings/{session}', [SettingsController::class, 'deleteSession']);
    Route::put('/settings/{key}', [SettingsController::class, 'updateSetting']);

    // Exports.
    Route::get('/export/character/{character}', [PlayerCharacterController::class, 'export']);

    // Tools
    Route::get('/tools/config', [ToolController::class, 'config']);
    Route::get('/vehicles', [ToolController::class, 'vehicles']);
    Route::get('/weapons', [ToolController::class, 'weapons']);
    Route::get('/weapons/{hash}', [ToolController::class, 'searchWeapons']);

    // Test.
    Route::get('/test/logs/{action}', [TestController::class, 'logs']);
    Route::get('/test/smart_watch', [TestController::class, 'smartWatchLeaderboard']);
    Route::get('/test/bans', [TestController::class, 'banLeaderboard']);
    Route::get('/test/modders', [TestController::class, 'moddingBans']);
    Route::get('/test/staff', [TestController::class, 'staffPlaytime']);
    Route::get('/test/finance', [TestController::class, 'finance']);
    Route::get('/test/staff_activity', [TestController::class, 'staffActivity']);
    Route::get('/test/staff_activity_2', [TestController::class, 'staffActivity2']);
    Route::get('/test/user_statistics/{player}', [TestController::class, 'userStatistics']);
    Route::get('/test/nancy_statistics', [TestController::class, 'nancyStatistics']);

    // Graphs.
    Route::get('/graph/bans', [GraphController::class, 'systemBans']);
    Route::get('/graph/bans/{type}', [GraphController::class, 'systemBansType']);
    Route::get('/graph/crashes', [GraphController::class, 'crashes']);
    Route::get('/graph/crashes/{type}', [GraphController::class, 'crashTypes']);
    Route::get('/graph/gems', [GraphController::class, 'minedGems']);

    // API.
    Route::get('/api/crafting', [ApiController::class, 'crafting']);
    Route::get('/api/character/{character}', [ApiController::class, 'character']);
    Route::get('/api/debug', [ApiController::class, 'debug']);
    Route::get('/api/config/{cluster}/{key}', [ApiController::class, 'config']);

    // Data routes.
    Route::get('/__data/ban_exceptions', [DataController::class, 'banExceptions']);

    // Generic playground route.
    Route::get('/test/test', [TestController::class, 'test']);
});

Route::group(['middleware' => ['staff', 'session'], 'prefix' => 'api'], function () {
    // Character info api
    Route::post('characters', [PlayerCharacterController::class, 'getCharacters']);

    // Screenshot api
    Route::post('screenshot/{server}/{id}', [PlayerRouteController::class, 'screenshot']);
    Route::post('capture/{server}/{id}/{duration}', [PlayerRouteController::class, 'capture']);

    // Overwatch.
    Route::get('randomScreenshot', [OverwatchController::class, 'getRandomScreenshot']);
});

Route::group(['prefix' => 'debug', 'middleware' => ['session']], function () {
    // log frontend errors
    Route::post('log', function (Request $request) {
        if (true) {
            abort(401);
        }

        $user     = user() ?? abort(401);
        $username = $user ? $user->player_name : 'N/A';

        $error = $request->json('entry');
        $href  = $request->json('href');
        if (!$error || !is_string($error) || !$href || !is_string($href)) {
            abort(400);
        }

        $href  = substr($href, 0, 150);
        $error = substr($error, 0, 500);
        $key   = sessionKey();

        $entry = '[' . $key . ' - ' . $username . '] ' . $href . ' - ' . $error;
        $file  = storage_path('logs/' . CLUSTER . '_frontend.log');

        put_contents($file, $entry . PHP_EOL, FILE_APPEND);

        abort(200);
    });
});

Route::get('hash/{hash}', function (string $hash) {
    $hash = trim($hash);

    $identifier = false;

    if ($hash && preg_match('/^[a-z0-9-]+$/im', $hash)) {
        $identifier = Ban::find($hash);
    }

    return (new Response([
        'valid' => !!$identifier,
    ], 200))->header('Content-Type', 'application/json');
});
