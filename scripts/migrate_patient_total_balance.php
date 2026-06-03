<?php

declare(strict_types=1);

/**
 * CLI: php scripts/migrate_patient_total_balance.php
 * Adds manual total_balance column and backfills from previous calculated balances.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

load_model('Patient');
load_model('Visit');
load_model('PaymentSettings');

try {
    $pdo = db();
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'patients'
           AND COLUMN_NAME = 'total_balance'"
    );
    $exists = (int) $stmt->fetchColumn() > 0;

    if (!$exists) {
        $pdo->exec(
            'ALTER TABLE patients
             ADD COLUMN total_balance DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER payment_paid_amount'
        );
        echo "Added patients.total_balance column.\n";
    } else {
        echo "Column patients.total_balance already exists.\n";
    }

    $patients = $pdo->query(
        'SELECT id, patient_code, payment_amount, payment_gst_amount, payment_method, payment_status, payment_paid_amount
         FROM patients
         WHERE patient_code IS NOT NULL'
    )->fetchAll();

    $update = $pdo->prepare(
        'UPDATE patients SET total_balance = :amount WHERE id = :id'
    );

    $updated = 0;
    foreach ($patients as $patient) {
        $patientId = (int) $patient['id'];
        $visits = Visit::listForPatient($patientId);
        $balance = Patient::totalOutstandingBalance($patient, $visits);

        $update->execute([
            'amount' => $balance,
            'id' => $patientId,
        ]);
        $updated++;
    }

    echo "Backfilled total_balance for {$updated} patient(s).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
