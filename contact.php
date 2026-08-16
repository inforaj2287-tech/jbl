<?php
/**
 * contact.php
 * Handles the "Send a message" contact form (see script.js -> submitForm).
 */

declare(strict_types=1);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.');
}

$name    = clean($_POST['name'] ?? '');
$phone   = clean($_POST['phone'] ?? '');
$email   = clean($_POST['email'] ?? '');
$message = clean($_POST['message'] ?? '');

$errors = [];
if ($name === '')                                            $errors[] = 'Name is required.';
if (!preg_match('/^[0-9]{10}$/', $phone))                     $errors[] = 'Enter a valid 10-digit phone number.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))               $errors[] = 'Enter a valid email address.';
if ($message === '')                                          $errors[] = 'Message cannot be empty.';

if (!empty($errors)) {
    json_response(false, implode(' ', $errors));
}

$record = [
    'name'       => $name,
    'phone'      => $phone,
    'email'      => $email,
    'message'    => $message,
    'created_at' => date('c'),
];

$saved = append_json_record(CONTACTS_FILE, $record);

if (!$saved) {
    json_response(false, 'Could not send your message right now. Please try again or call 080-4684-4684.');
}

// In production: send an email/Slack notification to the dispatch team here,
// e.g. mail('info@jbtravelsbelagavi.com', 'New enquiry', ...);

json_response(true, 'Thanks — your message has been received. Our team will reach out shortly.');
