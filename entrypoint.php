<?php

declare(strict_types=1);

use Symplify\MonorepoSplit\Config;
use Symplify\MonorepoSplit\ConfigFactory;
use Symplify\MonorepoSplit\Exception\ConfigurationException;

require_once __DIR__ . '/src/autoload.php';

note('Resolving configuration...');

$configFactory = new ConfigFactory();
try {
    $config = $configFactory->create(getenv());
} catch (ConfigurationException $configurationException) {
    error($configurationException->getMessage());
    exit(0);
}

setupGitCredentials($config);

$baseDir = getcwd();
$targetRepository = $config->getGitRepositorySsh();
$workingDirectory = sprintf('%s/%s', sys_get_temp_dir(), 'monorepo_split');

note('Persist SSH key');
exec(sprintf('echo "%s" > /root/.ssh/id_rsa', $config->getAccessToken()));
exec('chmod 600 /root/.ssh/id_rsa');

note('Copying project files to the split directory');
exec(sprintf('cp -ra . %s', $workingDirectory));

note('Changing directory to the split directory');
chdir($workingDirectory);

note('Getting current branch name');
$currentBranch = exec('git branch --show-current');

note('Removing origin remote for security reasons');
exec('git remote remove origin');

note('Filtering package\'s directory');
exec(sprintf('git filter-repo --subdirectory-filter %s --force', $config->getPackageDirectory()));

note('Adding target repository as remote');
exec(sprintf('git remote add split_target %s', $targetRepository));

note('Pushing to target repository');
exec(sprintf('git push split_target %s:%s --force', $currentBranch, $config->getBranch() ?? 'main'));

note('Changing directory back to the original directory');
chdir($baseDir);


// $changedFiles is an array that contains the list of modified files, and is empty if there are no changes.

//if ($changedFiles) {
//    note('Adding git commit');
//
//    exec_with_output_print('git add .');
//
//    $message = sprintf('Pushing git commit with "%s" message to "%s"', $commitMessage, $config->getBranch());
//    note($message);
//
//    exec("git commit --message '{$commitMessage}'");
//    exec('git push --quiet origin ' . $config->getBranch());
//} else {
//    note('No files to change');
//}
//
//
//// push tag if present
//if ($config->getTag()) {
//    $message = sprintf('Publishing "%s"', $config->getTag());
//    note($message);
//
//    $commandLine = sprintf('git tag %s -m "%s"', $config->getTag(), $message);
//    exec_with_note($commandLine);
//
//    exec_with_note('git push --quiet origin ' . $config->getTag());
//}


function createCommitMessage(string $commitSha): string
{
    exec("git show -s --format=%B {$commitSha}", $outputLines);
    return $outputLines[0] ?? '';
}


function note(string $message): void
{
    echo PHP_EOL . PHP_EOL . "\033[0;33m[NOTE] " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}

function error(string $message): void
{
    echo PHP_EOL . PHP_EOL . "\033[0;31m[ERROR] " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}




function list_directory_files(string $directory): void
{
    exec_with_output_print('ls -la ' . $directory);
}


/********************* helper functions *********************/

function exec_with_note(string $commandLine): void
{
    note('Running: ' . $commandLine);
    exec($commandLine);
}


function exec_with_output_print(string $commandLine): void
{
    exec($commandLine, $outputLines);
    echo implode(PHP_EOL, $outputLines);
}


function setupGitCredentials(Config $config): void
{
    if ($config->getUserName()) {
        exec('git config --global user.name ' . $config->getUserName());
    }

    if ($config->getUserEmail()) {
        exec('git config --global user.email ' . $config->getUserEmail());
    }
}
