<?php

// Default options test

require_once __DIR__ . '/../vendor/autoload.php';

use SatHub\CliTable\CliTable;
use SatHub\CliTable\CliTableManipulator;

$data = include('data.php');

$table = new CliTable;
$table->setTableColor('blue');
$table->setHeaderColor('cyan');
$table->addField('First Name', 'firstName',    false,                               'white');
$table->addField('Last Name',  'lastName',     false,                               'white');
$table->addField('Hobbies',    'hobbies');
$table->addField('DOB',        'dobTime',      new CliTableManipulator('datelong'));
$table->addField('Admin',      'isAdmin',      new CliTableManipulator('yesno'),    'yellow');
$table->addField('Last Seen',  'lastSeenTime', new CliTableManipulator('nicetime'), 'red');
$table->addField('Expires',    'expires',      new CliTableManipulator('duetime'),  'green');
$table->injectData($data);
$table->display();
