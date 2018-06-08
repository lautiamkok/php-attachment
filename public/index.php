<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

// PSR 7 standard.
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Views\PhpRenderer;
use \Monsoon\Sample;
use \Monsoon\Mailer;

// Bootstrap the app environment.
chdir(dirname(__DIR__));
require 'bootstrap.php';

// Create and configure Slim app
$config = ['settings' => [
    // To see the whole error logging text.
    // @ref: http://help.slimframework.com/discussions/problems/11471-slim-v3-errors
    'displayErrorDetails' => true
]];

// Get an instance of Slim.
$app = new \Slim\App($config);

// Get container
$container = $app->getContainer();

// Register component on container
$container['view'] = function ($container) {
    return new \Slim\Views\PhpRenderer('view/');
};

// Disabling error Slim handling.
// To completely disable Slim’s error handling, simply remove the error handler from the container.
// https://www.slimframework.com/docs/v3/handlers/error.html
unset($app->getContainer()['errorHandler']);
unset($app->getContainer()['phpErrorHandler']);

$app->get('/', function (Request $request, Response $response, $args) {
    $whitelist = ['image/jpeg', 'image/png'];
    var_dump(count($whitelist));

    $response = $this->view->render($response, 'index.html', []);
    return $response;
});

$app->post('/feedback', function (Request $request, Response $response, $args) {

    // Dummy data.
    $emailsTo = [
        0 => [
            'name' => 'Recipient 1',
            'email' => 'recipient1@example.com'
        ],
        1 => [
            'name' => 'Recipient 2',
            'email' => 'recipient2@example.com'
        ]
    ];

    $emailsCc = [
        0 => [
            'name' => 'Recipient 3',
            'email' => 'recipient3@example.com'
        ],
        1 => [
            'name' => 'Recipient 4',
            'email' => 'recipient4@example.com'
        ]
    ];

    $emailsBcc = [
        0 => [
            'name' => 'Recipient 5',
            'email' => 'recipient5@example.com'
        ],
        1 => [
            'name' => 'Recipient 6',
            'email' => 'recipient6@example.com'
        ]
    ];

    // Default status.
    $status = 200;

    // Try and catch the result.
    try {
        $mailer = new Mailer('public/uploads/', 1, ['image/jpeg', 'image/png']);
        $result = $mailer->sendMail($emailsTo, $emailsCc, $emailsBcc);
        $data = [
            "status" => $status,
            "message" => $result
        ];
    } catch (\Exception $error) {
        $status = $error->getCode();
        $data = [
            "status" => $status,
            "message" => $error->getMessage()
        ];
    };

    $response->getBody()->write(json_encode($data));
    return $response
        ->withStatus($status)
        ->withHeader('Content-type', 'application/json');
});

// Run the application!
$app->run();
