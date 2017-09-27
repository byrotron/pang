<?php

namespace Pang\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InstallCommand extends Command {

  protected $_dbname;
  protected $_app;
  protected $_dir;
  protected $_user = 'root';
  protected $_password = '';
  protected $_host = 'localhost';
  protected $_slim = false;

  protected function configure() {
    
    $this->setName('install')
      ->setDescription('Installs the app')
      ->setHelp('To help you create the app.')
      ->addOption('app', 'a', InputOption::VALUE_REQUIRED, 'The app name, this will also create the database')
      ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'Your database username, (default: root)')
      ->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, 'The directory you would like to add the app to, (default: cwd)')
      ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Your database password, (default: )')
      ->addOption('dbname', 'db', InputOption::VALUE_OPTIONAL, 'Database name if you want it to be different , (default: the provided app option)' )
      ->addOption('host', 'o', InputOption::VALUE_OPTIONAL, 'Your database host, (default: localhost)' )
      ->addOption('slim', 's', InputOption::VALUE_NONE, 'Do not install skeleton application, install angular.io starter app , (default: false)' );

  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    $output->writeln("This app will be create with the following information");
    $this->_app = $input->getOption("app");

    $this->_dir = !empty($input->getOption("dir")) ? $input->getOption("dir") : getcwd();
    $this->_user = !empty($input->getOption("user")) ? $input->getOption("user") : $this->_user;
    $this->_password = !empty($input->getOption("password")) ? $input->getOption("password") : $this->_password;
    $this->_dbname = !empty($input->getOption("dbname")) ? $input->getOption("dbname") : $this->_app;
    $this->_host = !empty($input->getOption("host")) ? $input->getOption("host") : $this->_host;
    $this->_slim = !empty($input->getOption("slim")) ? $input->getOption("slim") : $this->_slim;

    $this->createDatabase($output);
    $this->cloneApplication($output);
    $this->changeDirectoryName($output);
    $this->insertIntoConfig($output);
    $this->composerInstall($output);
    $this->npmInstall($output);

  }

  function createDatabase($output) {

    $mysqli = new \mysqli($this->_host, $this->_user, $this->_password);

    if ($mysqli->connect_errno) {
      throw new Exception("Connect failed: %s\n", $mysqli->connect_error);
    }

    $style = new OutputFormatterStyle('red', 'yellow', array('bold', 'blink'));
    $output->getFormatter()->setStyle('fire', $style);

    if($result = $mysqli->query("CREATE DATABASE " . $this->_dbname) === true) {
      $output->writeln('Database successfully created');
    } else {
      $output->writeln($mysqli->error);
    }

    $mysqli->close();
    
  }

  function changeDirectoryName($output) {

    $old_dir = $this->_dir . "/pang_skeleton";
    $new_dir = $this->_dir . "/" . $this->_app;
    if(rename($old_dir, $new_dir) === true) {
      $output->writeln('Renamed the directory to ' . $this->_app);
    } else {
      $output->writeln('Unable to rename directory to ' . $this->_app);
    }

  }

  function insertIntoConfig($output) {
    
    $dir = $this->_dir . "/" . $this->_app;

    $config = json_decode(file_get_contents($dir . "/config/development-config.json" ), true);
    $config["database"]["db_name"] = $this->_dbname;
    $config["database"]["host"] = $this->_host;
    $config["database"]["user"] = $this->_user;
    $config["database"]["password"] = $this->_password;

    file_put_contents($dir . "/config/development-config.json", json_encode($config, JSON_PRETTY_PRINT));

  }
  
  function cloneApplication($output) {
    $pb = new ProcessBuilder(["git", "clone", "https://github.com/byrotron/pang_skeleton.git"]);
    $process = $pb->setWorkingDirectory($this->_dir)
      ->getProcess();

    try {
      $process->mustRun();
    
      foreach ($process as $type => $data) {
         
        $output->writeln($data);

      }
    } catch (ProcessFailedException $e) {

      $output->writeln($e->getMessage());
    }

  }

  function composerInstall($output) {

    $pb = new ProcessBuilder(["composer", "install"]);
    $process = $pb->setWorkingDirectory($this->_dir . "/" . $this->_app)
      ->setTimeout(null)
      ->getProcess();

    try {
      $process->mustRun();
    
      foreach ($process as $type => $data) {
         
        $output->writeln($data);

      }
    } catch (ProcessFailedException $e) {

      $output->writeln($e->getMessage());
    }

  }

  function npmInstall($output) {
    $pb = new ProcessBuilder(["npm", "install"]);
    $process = $pb->setWorkingDirectory($this->_dir . "/" . $this->_app . "/public_html")
      ->setTimeout(null)
      ->getProcess();

    try {
      $process->mustRun();
    
      foreach ($process as $type => $data) {
         
        $output->writeln($data);

      }
    } catch (ProcessFailedException $e) {

      $output->writeln($e->getMessage());
    }
  }
}