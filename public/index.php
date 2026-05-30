<?php
/**
 * Front controller.
 * Routes are "METHOD /path" => [Class, 'method'] (or a closure).
 * Path segments wrapped in {name} are captured and passed positionally
 * to the handler.
 */

// Under the PHP built-in dev server, let real files in public/ be served
// directly — same effect as the Apache .htaccess rule that skips existing
// files. (No-op in production where Apache/Nginx does this for us.)
if (PHP_SAPI === 'cli-server') {
    $__path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($__path !== '/' && is_file(__DIR__ . $__path)) {
        return false;
    }
}

require __DIR__ . '/../includes/bootstrap.php';

$reqPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
$reqPath = rtrim($reqPath, '/') ?: '/';
$method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/* --- Routes ------------------------------------------------------------- */

$routes = [
    // Public
    'GET /'         => fn() => Auth::check()
                            ? redirect(Auth::dashboardUrl(Auth::role()))
                            : redirect('/login'),
    'GET /login'    => [AuthController::class, 'showLogin'],
    'POST /login'   => [AuthController::class, 'doLogin'],
    'GET /logout'   => [AuthController::class, 'logout'],

    // Role dashboards
    'GET /admin/dashboard'       => [AdminController::class, 'dashboard'],
    'GET /association/dashboard' => [AssociationController::class, 'dashboard'],
    'GET /panelist/dashboard'    => [PanelistController::class, 'dashboard'],
    'GET /school/dashboard'      => [SchoolController::class, 'dashboard'],

    // ===== Panelist :: Review queue =====
    'GET /panelist/review'                       => [PanelistReviewController::class, 'index'],
    'POST /panelist/review/approve-bulk'         => [PanelistReviewController::class, 'approveBulk'],
    'POST /panelist/review/{id}/approve'         => [PanelistReviewController::class, 'approve'],
    'POST /panelist/review/{id}/reject'          => [PanelistReviewController::class, 'reject'],
    'POST /panelist/review/{id}/revise'          => [PanelistReviewController::class, 'revise'],

    // ===== School :: Quiz engine =====
    'POST /school/quiz/start'             => [SchoolQuizController::class, 'start'],
    'GET /school/quiz'                    => [SchoolQuizController::class, 'show'],
    'GET /school/result'                  => [SchoolQuizController::class, 'result'],
    'GET /api/school/quiz/state'          => [SchoolQuizController::class, 'apiState'],
    'POST /api/school/quiz/answer'        => [SchoolQuizController::class, 'apiAnswer'],
    'POST /api/school/quiz/submit'        => [SchoolQuizController::class, 'apiSubmit'],

    // ===== Reports (printable HTML, opens in a new tab) =====
    'GET /reports/print/round/{id}'        => [ReportsController::class, 'round'],
    'GET /reports/print/final/{id}'        => [ReportsController::class, 'finalReport'],

    // ===== Panelist :: Results =====
    'GET /panelist/results'                       => [PanelistResultsController::class, 'index'],
    'GET /panelist/results/final'                 => [PanelistResultsController::class, 'finalView'],
    'GET /panelist/results/round/{id}'            => [PanelistResultsController::class, 'showRound'],
    'GET /panelist/results/round/{id}/qualify'    => [PanelistResultsController::class, 'qualifyForm'],
    'POST /panelist/results/round/{id}/qualify'   => [PanelistResultsController::class, 'qualifySave'],
    'POST /panelist/results/round/{id}/publish'   => [PanelistResultsController::class, 'publish'],
    'POST /panelist/results/round/{id}/unpublish' => [PanelistResultsController::class, 'unpublish'],

    // ===== Panelist :: Slot Builder (drag & drop) =====
    'GET /panelist/slots'                          => [PanelistSlotsController::class, 'index'],
    'GET /panelist/slots/{id}'                     => [PanelistSlotsController::class, 'build'],
    'POST /api/panelist/slot-questions/assign'     => [PanelistSlotsController::class, 'apiAssign'],
    'POST /api/panelist/slot-questions/unassign'   => [PanelistSlotsController::class, 'apiUnassign'],
    'POST /api/panelist/slot-questions/reorder'    => [PanelistSlotsController::class, 'apiReorder'],

    // ===== Panelist :: Master bank =====
    'GET /panelist/master'                       => [PanelistMasterController::class, 'index'],
    'GET /panelist/master/new'                   => [PanelistMasterController::class, 'create'],
    'POST /panelist/master'                      => [PanelistMasterController::class, 'store'],
    'GET /panelist/master/{id}/edit'             => [PanelistMasterController::class, 'edit'],
    'POST /panelist/master/{id}'                 => [PanelistMasterController::class, 'update'],
    'POST /panelist/master/{id}/delete'          => [PanelistMasterController::class, 'destroy'],

    // ===== Association :: Questions =====
    'GET /association/questions'              => [AssociationQuestionsController::class, 'index'],
    'GET /association/questions/new'          => [AssociationQuestionsController::class, 'create'],
    'POST /association/questions'             => [AssociationQuestionsController::class, 'store'],
    'POST /association/questions/submit'      => [AssociationQuestionsController::class, 'submitBulk'],
    'GET /association/questions/{id}/edit'    => [AssociationQuestionsController::class, 'edit'],
    'POST /association/questions/{id}'        => [AssociationQuestionsController::class, 'update'],
    'POST /association/questions/{id}/delete' => [AssociationQuestionsController::class, 'destroy'],

    // ===== Admin :: Associations =====
    'GET /admin/associations'              => [AdminAssociationsController::class, 'index'],
    'GET /admin/associations/new'          => [AdminAssociationsController::class, 'create'],
    'POST /admin/associations'             => [AdminAssociationsController::class, 'store'],
    'GET /admin/associations/{id}/edit'    => [AdminAssociationsController::class, 'edit'],
    'POST /admin/associations/{id}'        => [AdminAssociationsController::class, 'update'],
    'POST /admin/associations/{id}/delete' => [AdminAssociationsController::class, 'destroy'],

    // ===== Admin :: Schools =====
    'GET /admin/schools'              => [AdminSchoolsController::class, 'index'],
    'GET /admin/schools/new'          => [AdminSchoolsController::class, 'create'],
    'POST /admin/schools'             => [AdminSchoolsController::class, 'store'],
    'GET /admin/schools/{id}/edit'    => [AdminSchoolsController::class, 'edit'],
    'POST /admin/schools/{id}'        => [AdminSchoolsController::class, 'update'],
    'POST /admin/schools/{id}/delete' => [AdminSchoolsController::class, 'destroy'],

    // Combined school + team-login management (modal-driven)
    'GET /admin/schools/{id}'                          => [AdminSchoolsController::class, 'show'],
    'POST /admin/schools/{id}/logins'                  => [AdminSchoolsController::class, 'storeLogin'],
    'POST /admin/schools/{sid}/logins/{id}/reset'      => [AdminSchoolsController::class, 'resetLogin'],
    'POST /admin/schools/{sid}/logins/{id}/delete'     => [AdminSchoolsController::class, 'destroyLogin'],
    'POST /admin/schools/{sid}/logins/{id}'            => [AdminSchoolsController::class, 'updateLogin'],

    // ===== Admin :: Association Users =====
    'GET /admin/association-users'              => [AdminAssociationUsersController::class, 'index'],
    'GET /admin/association-users/new'          => [AdminAssociationUsersController::class, 'create'],
    'POST /admin/association-users'             => [AdminAssociationUsersController::class, 'store'],
    'GET /admin/association-users/{id}/edit'    => [AdminAssociationUsersController::class, 'edit'],
    'POST /admin/association-users/{id}'        => [AdminAssociationUsersController::class, 'update'],
    'POST /admin/association-users/{id}/delete' => [AdminAssociationUsersController::class, 'destroy'],

    // ===== Admin :: Expert Panelists =====
    'GET /admin/panelists'              => [AdminPanelistsController::class, 'index'],
    'GET /admin/panelists/new'          => [AdminPanelistsController::class, 'create'],
    'POST /admin/panelists'             => [AdminPanelistsController::class, 'store'],
    'GET /admin/panelists/{id}/edit'    => [AdminPanelistsController::class, 'edit'],
    'POST /admin/panelists/{id}'        => [AdminPanelistsController::class, 'update'],
    'POST /admin/panelists/{id}/delete' => [AdminPanelistsController::class, 'destroy'],

    // ===== Admin :: School Logins =====
    'GET /admin/school-logins'                  => [AdminSchoolLoginsController::class, 'index'],
    'GET /admin/school-logins/new'              => [AdminSchoolLoginsController::class, 'create'],
    'POST /admin/school-logins'                 => [AdminSchoolLoginsController::class, 'store'],
    'GET /admin/school-logins/{id}/edit'        => [AdminSchoolLoginsController::class, 'edit'],
    'POST /admin/school-logins/{id}'            => [AdminSchoolLoginsController::class, 'update'],
    'POST /admin/school-logins/{id}/delete'     => [AdminSchoolLoginsController::class, 'destroy'],
    'POST /admin/school-logins/{id}/reset'              => [AdminSchoolLoginsController::class, 'resetPassword'],
    'POST /admin/school-logins/{id}/send-credentials'   => [AdminSchoolLoginsController::class, 'sendCredentials'],

    // ===== Admin :: Email Templates =====
    'GET /admin/email-templates'                => [AdminEmailTemplatesController::class, 'index'],
    'GET /admin/email-templates/{key}/edit'     => [AdminEmailTemplatesController::class, 'edit'],
    'POST /admin/email-templates/{key}'         => [AdminEmailTemplatesController::class, 'update'],

    // ===== Admin :: Questions (read-only master pool) =====
    'GET /admin/questions'                      => [AdminQuestionsController::class, 'index'],

    // ===== Admin :: Settings =====
    'GET /admin/settings'                       => [AdminSettingsController::class, 'index'],
    'POST /admin/settings'                      => [AdminSettingsController::class, 'update'],

    // ===== Admin :: Reports =====
    'GET /admin/reports'                        => [AdminReportsController::class, 'index'],

    // ===== Admin :: Rounds =====
    'GET /admin/rounds'              => [AdminRoundsController::class, 'index'],
    'GET /admin/rounds/new'          => [AdminRoundsController::class, 'create'],
    'POST /admin/rounds'             => [AdminRoundsController::class, 'store'],
    'GET /admin/rounds/{id}/edit'    => [AdminRoundsController::class, 'edit'],
    'POST /admin/rounds/{id}'        => [AdminRoundsController::class, 'update'],
    'POST /admin/rounds/{id}/delete' => [AdminRoundsController::class, 'destroy'],

    // ===== Admin :: Slots =====
    'GET /admin/slots'                  => [AdminSlotsController::class, 'index'],
    'GET /admin/slots/new'              => [AdminSlotsController::class, 'create'],
    'POST /admin/slots'                 => [AdminSlotsController::class, 'store'],
    'GET /admin/slots/{id}/edit'        => [AdminSlotsController::class, 'edit'],
    'POST /admin/slots/{id}'            => [AdminSlotsController::class, 'update'],
    'POST /admin/slots/{id}/delete'     => [AdminSlotsController::class, 'destroy'],
    'GET /admin/slots/{id}/assign'      => [AdminSlotsController::class, 'assignForm'],
    'POST /admin/slots/{id}/assign'     => [AdminSlotsController::class, 'assignSave'],
    'POST /admin/slots/{id}/unassign'   => [AdminSlotsController::class, 'unassign'],
];

/* --- Dispatch ----------------------------------------------------------- */

foreach ($routes as $pattern => $handler) {
    [$rMethod, $rPath] = explode(' ', $pattern, 2);
    if ($rMethod !== $method) continue;

    if (!str_contains($rPath, '{')) {
        if ($rPath !== $reqPath) continue;
        $params = [];
    } else {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?<$1>[^/]+)', $rPath) . '$#';
        if (!preg_match($regex, $reqPath, $m)) continue;
        $params = array_values(array_filter(
            $m,
            static fn($k) => is_string($k),
            ARRAY_FILTER_USE_KEY
        ));
    }

    if (is_array($handler)) {
        [$class, $methodName] = $handler;
        (new $class)->$methodName(...$params);
    } else {
        $handler(...$params);
    }
    return;
}

http_response_code(404);
render('errors/404', ['title' => 'Not found — Olympics Run 2026']);
