<?php

namespace Pang\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InstallCommand extends Command {

  protected function configure() {
    
    $this->setName('install')
      ->setDescription('Installs the app')
      ->setHelp('To help you create the app.');

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $process = new Process('composer updates');
    $process->run(function ($type, $buffer) {
      echo 'OUT > '.$buffer;
    });

  }

}