<?php
declare(strict_types=1);

// Front Controller

// Caminhos base
$basePath = dirname(__DIR__);
$appPath  = $basePath . '/app';
// Composer autoload (if available)
$vendorAutoload = $basePath . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

// Carrega config
$config = require $appPath . '/config/config.php';

// Autoloader simples (PSR-4 básico)
spl_autoload_register(function ($class) use ($appPath) {
    $prefixes = [
        'Core\\' => $appPath . '/core/',
        'Controllers\\' => $appPath . '/controllers/',
        'Models\\' => $appPath . '/models/',
    ];
    foreach ($prefixes as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file = $dir . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// Helpers globais
function env(string $key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Inicializa núcleo (antes da sessão para buscar settings do banco)
Core\Config::init($config);
Core\Database::init();

// Inicia sessão com configurações seguras, lendo lifetime do Settings se existir
ini_set('session.use_strict_mode', '1');
session_name($config['security']['session_name']);
$sessionLifetime = (int)($config['security']['session_lifetime'] ?? 28800);
try {
    $set = new Models\Setting();
    $fromDb = (int)$set->get('session_lifetime', (string)$sessionLifetime);
    if ($fromDb > 0) { $sessionLifetime = $fromDb; }
} catch (\Throwable $e) {
    // fallback to config
}
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'domain' => '',
    'secure' => (bool)$config['security']['cookie_secure'],
    'httponly' => (bool)$config['security']['cookie_httponly'],
    'samesite' => $config['security']['cookie_samesite'],
]);
session_start();

// Continua inicialização
Core\Auth::init();

// Define rotas
// Detecta automaticamente o base_url a partir do caminho do script, caso não esteja definido
$detectedBase = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$detectedBase = rtrim($detectedBase, '/');
if ($detectedBase === '') { $detectedBase = '/'; }
$cfgBase = $config['app']['base_url'] ?? null;
// Se o config estiver vazio ou for '/', prefira o detectado (isso evita 404 quando o app roda sob /public)
if ($cfgBase === null || $cfgBase === '' || $cfgBase === '/') {
    $baseUrl = $detectedBase ?: '/';
} else {
    $baseUrl = $cfgBase;
}
$router = new Core\Router($baseUrl);

// Rotas públicas
$router->get('/', 'Controllers\\AuthController@login');
$router->get('/login', 'Controllers\\AuthController@login');
$router->post('/login', 'Controllers\\AuthController@doLogin');
$router->get('/logout', 'Controllers\\AuthController@logout');

// Middleware de autenticação para rotas internas
$router->group('/admin', function($r) {
    // Dashboard
    $r->get('/', 'Controllers\\DashboardController@index');
    
    // Clientes
    $r->get('/clients', 'Controllers\\ClientsController@index');
    $r->get('/clients/search', 'Controllers\\ClientsController@search');
    $r->get('/clients/new', 'Controllers\\ClientsController@new');
    $r->post('/clients/create', 'Controllers\\ClientsController@create');
    $r->post('/clients/create-ajax', 'Controllers\\ClientsController@createAjax');
    $r->get('/clients/edit', 'Controllers\\ClientsController@edit');
    $r->post('/clients/update', 'Controllers\\ClientsController@update');
    $r->post('/clients/delete', 'Controllers\\ClientsController@delete');

    // Configurações
    $r->get('/settings', 'Controllers\\SettingsController@index');
    $r->post('/settings/save', 'Controllers\\SettingsController@save');
    $r->get('/settings/calculations', 'Controllers\\SettingsController@calculations');
    $r->get('/settings/calculations-simple', 'Controllers\\SettingsController@calculationsSimple');

    // Vendas
    $r->get('/sales', 'Controllers\\SalesController@index');
    $r->get('/sales/new', 'Controllers\\SalesController@new');
    $r->post('/sales/create', 'Controllers\\SalesController@create');
    $r->get('/sales/edit', 'Controllers\\SalesController@edit');
    $r->post('/sales/update', 'Controllers\\SalesController@update');
    $r->post('/sales/delete', 'Controllers\\SalesController@delete');
    $r->get('/sales/search', 'Controllers\\SalesController@search');

    // Relatórios
    $r->get('/reports', 'Controllers\\ReportsController@index');
    $r->get('/reports/export-pdf', 'Controllers\\ReportsController@exportPdf');
    $r->get('/reports/cost-allocation.csv', 'Controllers\\ReportsController@costAllocationCsv');

    // Financeiro (admin)
    $r->get('/finance', 'Controllers\\FinanceController@index');
    $r->get('/finance/export-company.pdf', 'Controllers\\FinanceController@exportCompanyPdf');
    $r->get('/finance/export-company.xlsx', 'Controllers\\FinanceController@exportCompanyXlsx');
    $r->get('/finance/export-seller.pdf', 'Controllers\\FinanceController@exportSellerPdf');
    $r->get('/finance/export-costs.csv', 'Controllers\\FinanceController@exportCostsCsv');
    $r->get('/finance/export-attendances.csv', 'Controllers\\FinanceController@exportAttendancesCsv');
    $r->get('/finance/export-attendances.xlsx', 'Controllers\\FinanceController@exportAttendancesXlsx');

    // Logs
    $r->get('/logs', 'Controllers\\LogsController@index');

    // Usuários (apenas admin)
    $r->get('/users', 'Controllers\\UsersController@index');
    $r->get('/users/new', 'Controllers\\UsersController@new');
    $r->post('/users/create', 'Controllers\\UsersController@create');
    $r->get('/users/edit', 'Controllers\\UsersController@edit');
    $r->post('/users/update', 'Controllers\\UsersController@update');
    $r->post('/users/delete', 'Controllers\\UsersController@delete');
    $r->get('/users/options', 'Controllers\\UsersController@options');
    // Admin aliases
    $r->get('/admin/users', 'Controllers\\UsersController@index');
    $r->get('/admin/users/new', 'Controllers\\UsersController@new');
    $r->post('/admin/users/create', 'Controllers\\UsersController@create');
    $r->get('/admin/users/edit', 'Controllers\\UsersController@edit');
    $r->post('/admin/users/update', 'Controllers\\UsersController@update');
    $r->post('/admin/users/delete', 'Controllers\\UsersController@delete');
    $r->get('/admin/users/options', 'Controllers\\UsersController@options');

    // Comissões
    $r->get('/commissions', 'Controllers\\CommissionsController@index'); // admin
    $r->post('/commissions/recalc', 'Controllers\\CommissionsController@recalc'); // admin
    $r->get('/commissions/me', 'Controllers\\CommissionsController@me'); // seller/manager/admin
    $r->get('/commissions/export', 'Controllers\\CommissionsController@exportCsv'); // admin
    $r->get('/commissions/debug', 'Controllers\\CommissionsController@debug'); // seller/manager/admin
    // Atendimentos
    $r->get('/attendances', 'Controllers\\AttendancesController@index');
    $r->post('/attendances/save', 'Controllers\\AttendancesController@save');
    $r->get('/attendances/export', 'Controllers\\AttendancesController@exportCsv');
    $r->get('/attendances/export-pdf', 'Controllers\\AttendancesController@exportPdf');
    $r->post('/attendances/delete', 'Controllers\\AttendancesController@delete');

    // Custos
    $r->get('/costs', 'Controllers\\CostsController@index');
    $r->post('/costs/create', 'Controllers\\CostsController@create');
    $r->post('/costs/update', 'Controllers\\CostsController@update');
    $r->post('/costs/delete', 'Controllers\\CostsController@delete');
    $r->post('/costs/recurrence/run', 'Controllers\\CostsController@runRecurrence');

    // Doações (admin)
    $r->get('/donations', 'Controllers\\DonationsController@index');
    $r->post('/donations/create', 'Controllers\\DonationsController@create');
    $r->post('/donations/update', 'Controllers\\DonationsController@update');
    $r->post('/donations/cancel', 'Controllers\\DonationsController@cancel');
    $r->get('/donations/export', 'Controllers\\DonationsController@exportCsv');

    // Compras (admin ou comprador designado)
    $r->get('/purchases', 'Controllers\\PurchasesController@index');
    $r->get('/purchases/new', 'Controllers\\PurchasesController@new');
    $r->post('/purchases/create', 'Controllers\\PurchasesController@create');
    $r->post('/purchases/update', 'Controllers\\PurchasesController@update');
    $r->get('/purchases/export', 'Controllers\\PurchasesController@exportCsv');

    // Containers (admin/manager)
    $r->get('/containers', 'Controllers\\ContainersController@index');
    $r->get('/containers/new', 'Controllers\\ContainersController@new');
    $r->post('/containers/create', 'Controllers\\ContainersController@create');
    $r->get('/containers/edit', 'Controllers\\ContainersController@edit');
    $r->post('/containers/update', 'Controllers\\ContainersController@update');
    $r->post('/containers/delete', 'Controllers\\ContainersController@delete');

    // Notificações internas
    $r->get('/notifications', 'Controllers\\NotificationsController@index');
    $r->get('/notifications/new', 'Controllers\\NotificationsController@new');
    $r->post('/notifications/create', 'Controllers\\NotificationsController@create');
    $r->post('/notifications/mark-read', 'Controllers\\NotificationsController@markRead');
    $r->post('/notifications/mark-unread', 'Controllers\\NotificationsController@markUnread');
    $r->post('/notifications/archive', 'Controllers\\NotificationsController@archive');
    $r->post('/notifications/delete', 'Controllers\\NotificationsController@delete');

    // Demandas
    $r->get('/demands', 'Controllers\\DemandsController@index');
    $r->get('/demands/dashboard', 'Controllers\\DemandsController@dashboard');
    $r->post('/demands/create', 'Controllers\\DemandsController@create');
    $r->post('/demands/assign', 'Controllers\\DemandsController@assign');
    $r->post('/demands/status', 'Controllers\\DemandsController@status');
    $r->post('/demands/update', 'Controllers\\DemandsController@update');

    // Projetos
    $r->get('/projects', 'Controllers\\ProjectsController@index');
    $r->get('/projects/options', 'Controllers\\ProjectsController@options');
    $r->post('/projects/create', 'Controllers\\ProjectsController@create');
    $r->post('/projects/update', 'Controllers\\ProjectsController@update');
    $r->get('/projects/view', 'Controllers\\ProjectsController@view');

    // Documentações e Procedimentos
    $r->get('/documentations', 'Controllers\\DocumentationsController@index');
    $r->get('/documentations/new', 'Controllers\\DocumentationsController@new');
    $r->post('/documentations/create', 'Controllers\\DocumentationsController@create');
    $r->get('/documentations/view', 'Controllers\\DocumentationsController@view');
    $r->get('/documentations/edit', 'Controllers\\DocumentationsController@edit');
    $r->post('/documentations/update', 'Controllers\\DocumentationsController@update');
    $r->post('/documentations/publish', 'Controllers\\DocumentationsController@publish');
    $r->post('/documentations/set-slug', 'Controllers\\DocumentationsController@setSlug');
    $r->post('/documentations/email-add', 'Controllers\\DocumentationsController@emailAdd');
    $r->post('/documentations/email-remove', 'Controllers\\DocumentationsController@emailRemove');
    $r->post('/documentations/comment-add', 'Controllers\\DocumentationsController@commentAdd');

    // Hospedagens
    $r->get('/hostings', 'Controllers\\HostingsController@index');
    $r->post('/hostings/create', 'Controllers\\HostingsController@create');
    $r->post('/hostings/update', 'Controllers\\HostingsController@update');
    $r->post('/hostings/delete', 'Controllers\\HostingsController@delete');

    // Ativos de Hospedagem (Sites/Sistemas/E-mails)
    $r->get('/hosting-assets', 'Controllers\\HostingAssetsController@index');
    $r->post('/hosting-assets/create', 'Controllers\\HostingAssetsController@create');
    $r->post('/hosting-assets/update', 'Controllers\\HostingAssetsController@update');
    $r->post('/hosting-assets/delete', 'Controllers\\HostingAssetsController@delete');
    $r->post('/hosting-assets/refresh-dns', 'Controllers\\HostingAssetsController@refreshDns');
    $r->post('/hosting-assets/refresh-dns-all', 'Controllers\\HostingAssetsController@refreshDnsAll');

    // Clients options for autocomplete
    $r->get('/clients/options', 'Controllers\\ClientsController@options');
    $r->get('/admin/clients/options', 'Controllers\\ClientsController@options');
    // Clients index aliases
    $r->get('/clients', 'Controllers\\ClientsController@index');
    $r->get('/admin/clients', 'Controllers\\ClientsController@index');

    // Site Clients (for hostings/sites/emails)
    $r->get('/site-clients', 'Controllers\\SiteClientsController@index');
    $r->post('/site-clients/create', 'Controllers\\SiteClientsController@create');
    $r->post('/site-clients/update', 'Controllers\\SiteClientsController@update');
    $r->post('/site-clients/delete', 'Controllers\\SiteClientsController@delete');
    $r->get('/site-clients/options', 'Controllers\\SiteClientsController@options');

    // Settings (DNS/Cloudflare inside)
    $r->get('/admin/settings', 'Controllers\\DnsSettingsController@index');
    $r->post('/admin/settings/save', 'Controllers\\DnsSettingsController@save');
    // Backward-compatible routes
    $r->get('/admin/settings/dns', 'Controllers\\DnsSettingsController@index');
    $r->post('/admin/settings/dns/save', 'Controllers\\DnsSettingsController@save');
    $r->get('/settings/dns', 'Controllers\\DnsSettingsController@index');
    $r->post('/settings/dns/save', 'Controllers\\DnsSettingsController@save');

    // Backups (admin)
    $r->get('/backups', 'Controllers\\BackupsController@index');
    $r->post('/backups/run', 'Controllers\\BackupsController@run');
    $r->post('/backups/delete', 'Controllers\\BackupsController@delete');
    $r->post('/backups/restore', 'Controllers\\BackupsController@restore');
    $r->post('/backups/save-settings', 'Controllers\\BackupsController@saveSettings');

    // Áreas Técnicas (admin)
    $r->get('/documentation-areas', 'Controllers\\DocumentationAreasController@index');
    $r->post('/documentation-areas/create', 'Controllers\\DocumentationAreasController@create');
    $r->post('/documentation-areas/update', 'Controllers\\DocumentationAreasController@update');
    $r->post('/documentation-areas/delete', 'Controllers\\DocumentationAreasController@delete');

    // Approvals (supervisor flow)
    $r->get('/approvals', 'Controllers\\ApprovalsController@index');
    $r->post('/approvals/approve', 'Controllers\\ApprovalsController@approve');
    $r->post('/approvals/reject', 'Controllers\\ApprovalsController@reject');

    // Time Off
    $r->post('/timeoff/create', 'Controllers\\TimeOffController@createToday');

    // Vendas Internacionais
    $r->get('/international-sales', 'Controllers\\InternationalSalesController@index');
    // Alias: manter compatibilidade com /sales
    $r->get('/sales', 'Controllers\\RedirectController@salesToInternationalSales');
    $r->get('/international-sales/new', 'Controllers\\InternationalSalesController@new');
    $r->post('/international-sales/create', 'Controllers\\InternationalSalesController@create');
    $r->get('/international-sales/edit', 'Controllers\\InternationalSalesController@edit');
    $r->get('/international-sales/duplicate', 'Controllers\\InternationalSalesController@duplicate');
    $r->post('/international-sales/delete', 'Controllers\\InternationalSalesController@delete');
    $r->post('/international-sales/update', 'Controllers\\InternationalSalesController@update');
    $r->get('/international-sales/export', 'Controllers\\InternationalSalesController@exportCsv');
    $r->get('/international-sales/data', 'Controllers\\InternationalSalesController@data');

    // Simulador de Cálculo
    $r->get('/sales-simulator', 'Controllers\\SalesSimulatorController@index');
    $r->get('/sales-simulator/budgets', 'Controllers\\SimulatorBudgetsController@index');
    $r->post('/sales-simulator/budgets/save', 'Controllers\\SimulatorBudgetsController@save');
    $r->post('/sales-simulator/budgets/duplicate', 'Controllers\\SimulatorBudgetsController@duplicate');
    $r->post('/sales-simulator/budgets/delete', 'Controllers\\SimulatorBudgetsController@delete');
    $r->post('/sales-simulator/budgets/toggle-paid', 'Controllers\\SimulatorBudgetsController@togglePaid');
    // Lojas do simulador (apenas admin)
    $r->get('/simulator-stores', 'Controllers\\SimulatorStoresController@index');
    $r->post('/simulator-stores/create', 'Controllers\\SimulatorStoresController@create');
    $r->post('/simulator-stores/delete', 'Controllers\\SimulatorStoresController@delete');
    // Produtos do simulador (tela própria)
    $r->get('/simulator-products', 'Controllers\\SimulatorProductsController@index');
    $r->get('/simulator-products/template', 'Controllers\\SimulatorProductsController@template');
    $r->get('/simulator-products/import', 'Controllers\\SimulatorProductsController@importForm');
    $r->post('/simulator-products/import', 'Controllers\\SimulatorProductsController@import');
    $r->get('/simulator-products/new', 'Controllers\\SimulatorProductsController@new');
    $r->post('/simulator-products/create', 'Controllers\\SimulatorProductsController@create');
    $r->get('/simulator-products/edit', 'Controllers\\SimulatorProductsController@edit');
    $r->post('/simulator-products/update', 'Controllers\\SimulatorProductsController@update');
    $r->post('/simulator-products/delete', 'Controllers\\SimulatorProductsController@delete');
    // Produtos do simulador - integrações AJAX com a tela do simulador
    $r->get('/sales-simulator/products/search', 'Controllers\\SimulatorProductsController@search');
    $r->post('/sales-simulator/products/create-ajax', 'Controllers\\SimulatorProductsController@createAjax');

    // Relatório consolidado de produtos do simulador (admin/manager)
    $r->get('/sales-simulator/products-report', 'Controllers\\SimulatorProductsReportController@index');
    $r->get('/sales-simulator/products-report/product', 'Controllers\\SimulatorProductsReportController@product');
    $r->post('/sales-simulator/products-report/update-purchased', 'Controllers\\SimulatorProductsReportController@updatePurchased');
    $r->post('/sales-simulator/products-report/update-cash', 'Controllers\\SimulatorProductsReportController@updateCash');
    $r->post('/sales-simulator/products-report/save-fabiana-cash', 'Controllers\\SimulatorProductsReportController@saveFabianaCash');
    $r->get('/sales-simulator/products-report/export-pdf', 'Controllers\\SimulatorProductsReportController@exportPdf');

    // Dashboard financeiro para compras da Fabiana (apenas admin)
    $r->get('/sales-simulator/fabiana', 'Controllers\\FabianaPurchasesController@index');
    $r->post('/sales-simulator/fabiana/save-cash', 'Controllers\\FabianaPurchasesController@saveCashTotal');

    // Vendas Nacionais
    $r->get('/national-sales', 'Controllers\\NationalSalesController@index');
    $r->get('/national-sales/new', 'Controllers\\NationalSalesController@new');
    $r->post('/national-sales/create', 'Controllers\\NationalSalesController@create');
    $r->get('/national-sales/edit', 'Controllers\\NationalSalesController@edit');
    $r->get('/national-sales/duplicate', 'Controllers\\NationalSalesController@duplicate');
    $r->post('/national-sales/delete', 'Controllers\\NationalSalesController@delete');
    $r->post('/national-sales/update', 'Controllers\\NationalSalesController@update');
    $r->get('/national-sales/export', 'Controllers\\NationalSalesController@exportCsv');
    $r->get('/national-sales/data', 'Controllers\\NationalSalesController@data');

    // Mensagens Padrão
    $r->get('/message-templates', 'Controllers\\MessageTemplatesController@index');

    // Minha Conta
    $r->get('/account', 'Controllers\\AccountController@index');
    $r->post('/account/update-profile', 'Controllers\\AccountController@updateProfile');
    $r->post('/account/update-password', 'Controllers\\AccountController@updatePassword');

    // Metas e Previsões
    $r->get('/goals', 'Controllers\\GoalsController@index'); // admin
    $r->post('/goals/create', 'Controllers\\GoalsController@create');
    $r->post('/goals/update', 'Controllers\\GoalsController@update');
    $r->post('/goals/delete', 'Controllers\\GoalsController@delete');
    $r->get('/my/goals', 'Controllers\\MyGoalsController@index'); // vendedor
    $r->get('/costs/export', 'Controllers\\CostsController@exportCsv');
    $r->get('/donations/export', 'Controllers\\DonationsController@exportCsv');

    // Webhooks logs (admin/manager)
    $r->get('/webhooks/logs', 'Controllers\\WebhooksController@index');

    // API Calculator (admin settings)
    $r->get('/api-calc', 'Controllers\\ApiCalcController@settings');
    $r->post('/api-calc/save', 'Controllers\\ApiCalcController@save');
    // Admin aliases
    $r->get('/admin/api-calc', 'Controllers\\ApiCalcController@settings');
    $r->post('/admin/api-calc/save', 'Controllers\\ApiCalcController@save');
    $r->get('/admin/api-calc/logs', 'Controllers\\ApiCalcController@logs');
    $r->get('/api-calc/logs', 'Controllers\\ApiCalcController@logs');

    // Webhooks guide (admin)
    $r->get('/webhooks/guide', 'Controllers\\WebhooksController@guide');

    $r->get('/performance', 'Controllers\\PerformanceController@index');
    $r->get('/performance/export-seller.pdf', 'Controllers\\PerformanceController@exportSellerPdf');

    // Congelamento de período anterior (admin / cron)
    $r->post('/dashboard/freeze-previous-period', 'Controllers\\DashboardController@freezePreviousPeriod');

}, function() {
    return \Core\Auth::check();
}, '/login');

// Webhook endpoints (public, sem autenticação)
$router->post('/webhooks/containers', 'Controllers\\WebhooksController@containers');
$router->post('/webhooks/sales', 'Controllers\\WebhooksController@sales');
$router->post('/webhooks/demands', 'Controllers\\WebhooksController@demands');

// Public cron endpoint for backups (token required)
$router->get('/backups/cron-run', 'Controllers\\BackupsController@cronRun');

// Public cron endpoint for freezing previous period (token required)
$router->get('/cron/freeze-previous-period', 'Controllers\\DashboardController@cronFreezePreviousPeriod');

// API Calculator public endpoint (token via Authorization: Bearer <token>)
$router->post('/api/calc-net', 'Controllers\\ApiCalcController@compute');

// Public documentation viewing (email-gated)
$router->get('/docs', 'Controllers\\DocumentationsController@publicView');

// Dispatch
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
