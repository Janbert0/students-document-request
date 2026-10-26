<?php
require_once __DIR__ . '/functions.php';

const CONTACT_NUMBER_PATTERN = '/^\+?[0-9][0-9 ()\-]{6,18}$/';

/** Returns an error message for an unacceptable password, or null if it is acceptable. */
function validatePassword(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    // bcrypt ignores everything after 72 bytes, so longer passwords are rejected.
    if (strlen($password) > 72) {
        return 'Password must be 72 characters or fewer.';
    }
    return null;
}

/** Validates the student registration form. Returns field name => message. */
function validateRegistrationData(array $data): array
{
    $errors = [];

    $studentNumber = cleanInput($data, 'student_number');
    if ($studentNumber === '' || mb_strlen($studentNumber) > 30) {
        $errors['student_number'] = 'Enter your student ID number (30 characters or fewer).';
    }

    $firstName = cleanInput($data, 'first_name');
    if ($firstName === '' || mb_strlen($firstName) > 50) {
        $errors['first_name'] = 'Enter your first name (50 characters or fewer).';
    }

    $lastName = cleanInput($data, 'last_name');
    if ($lastName === '' || mb_strlen($lastName) > 50) {
        $errors['last_name'] = 'Enter your last name (50 characters or fewer).';
    }

    if (mb_strlen(cleanInput($data, 'middle_name')) > 50) {
        $errors['middle_name'] = 'Middle name must be 50 characters or fewer.';
    }

    $email = cleanInput($data, 'email');
    if ($email === '' || mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (mb_strlen(cleanInput($data, 'department')) > 100) {
        $errors['department'] = 'College / department must be 100 characters or fewer.';
    }

    if (mb_strlen(cleanInput($data, 'course')) > 100) {
        $errors['course'] = 'Course / program must be 100 characters or fewer.';
    }

    $contact = cleanInput($data, 'contact_number');
    if ($contact !== '' && !preg_match(CONTACT_NUMBER_PATTERN, $contact)) {
        $errors['contact_number'] = 'Enter a valid contact number, such as 09171234567.';
    }

    $password = rawInput($data, 'password');
    $passwordError = validatePassword($password);
    if ($passwordError !== null) {
        $errors['password'] = $passwordError;
    }

    if ($password !== rawInput($data, 'confirm_password')) {
        $errors['confirm_password'] = 'The passwords do not match.';
    }

    return $errors;
}

/** Validates a document request. Returns a list of messages, empty when valid. */
function validateRequestInput(array $data): array
{
    $messages = [];

    if ((int) ($data['document_type_id'] ?? 0) <= 0) {
        $messages[] = 'Select a document type.';
    }

    $copies = (int) ($data['copies'] ?? 0);
    if ($copies < 1 || $copies > MAX_COPIES) {
        $messages[] = 'Number of copies must be between 1 and ' . MAX_COPIES . '.';
    }

    $purpose = cleanInput($data, 'purpose');
    if ($purpose === '') {
        $messages[] = 'Enter the purpose of the request.';
    } elseif (mb_strlen($purpose) > 255) {
        $messages[] = 'Purpose must be 255 characters or fewer.';
    }

    if (mb_strlen(cleanInput($data, 'remarks')) > 1000) {
        $messages[] = 'Remarks must be 1000 characters or fewer.';
    }

    return $messages;
}

/**
 * Validates a document type from the admin form.
 * Returns [cleaned data, list of messages].
 */
function validateDocumentType(array $data): array
{
    $clean = [
        'name' => cleanInput($data, 'name'),
        'description' => cleanInput($data, 'description'),
        'requirements' => cleanInput($data, 'requirements'),
        'estimated_processing_days' => (int) ($data['estimated_processing_days'] ?? 0),
    ];

    $messages = [];

    if ($clean['name'] === '' || mb_strlen($clean['name']) > 100) {
        $messages[] = 'Document title is required (100 characters or fewer).';
    }
    if ($clean['description'] === '' || mb_strlen($clean['description']) > 500) {
        $messages[] = 'Description is required (500 characters or fewer).';
    }
    if (mb_strlen($clean['requirements']) > 500) {
        $messages[] = 'Requirements must be 500 characters or fewer.';
    }
    if ($clean['estimated_processing_days'] < 1 || $clean['estimated_processing_days'] > 30) {
        $messages[] = 'Processing time must be between 1 and 30 working days.';
    }

    return [$clean, $messages];
}
