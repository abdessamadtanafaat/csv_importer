#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use League\Csv\Reader;
use PDO;
use Symfony\Component\Console\Style\SymfonyStyle;

$console = new Application('CSV Importer CLI', '1.0');

// CSV Import Command
$console->register('import:csv')
    ->setDescription('Import a CSV file into the MySQL database')
    ->setDefinition([
        new InputArgument('csvFile', InputArgument::REQUIRED, 'The path to the CSV file'),
    ])
    ->setCode(function (InputInterface $input, OutputInterface $output) {
        $io = new SymfonyStyle($input, $output);

        $csvFilePath = $input->getArgument('csvFile');

        // Check if file exists
        if (!file_exists($csvFilePath)) {
            $io->error("CSV file not found: $csvFilePath");
            return Command::FAILURE;
        }

        // Measure the execution time
        $startTime = microtime(true);

        // Setup PDO for MySQL Database interaction
        $dsn = 'mysql:host=127.0.0.1;dbname=csv_db;charset=utf8mb4';
        $username = 'csv_user';
        $password = 'void2015';

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\PDOException $e) {
            $io->error("Database Connection Failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Prepare SQL for data insertion
        $stmt = $pdo->prepare("INSERT INTO customers 
            (customer_id, first_name, last_name, company, city, country, phone1, phone2, email, subscription_date, website) 
            VALUES (:customer_id, :first_name, :last_name, :company, :city, :country, :phone1, :phone2, :email, :subscription_date, :website)");

        // Read CSV file using PHP League CSV
        $csv = Reader::createFromPath($csvFilePath, 'r');
        $csv->setHeaderOffset(0); // Use first row as header

        // Start transaction
        $pdo->beginTransaction();

        $rowCount = 0;
        foreach ($csv as $record) {
            $stmt->execute([
                'customer_id' => $record['Customer Id'],
                'first_name' => $record['First Name'],
                'last_name' => $record['Last Name'],
                'company' => $record['Company'],
                'city' => $record['City'],
                'country' => $record['Country'],
                'phone1' => $record['Phone 1'],
                'phone2' => $record['Phone 2'],
                'email' => $record['Email'],
                'subscription_date' => $record['Subscription Date'],
                'website' => $record['Website'],
            ]);
            $rowCount++;
        }

        // Commit transaction
        $pdo->commit();

        // Measure execution time
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Output success message
        $io->success("Imported $rowCount rows successfully in $executionTime seconds.");
        return Command::SUCCESS;
    });

$console->run();
