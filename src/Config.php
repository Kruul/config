<?php

/*
 * Принимает параметры консоли и конфига, настраивает
 * окружение для протоколирования (консоль,файл...).
 * Назначение, обїединяет консольные команды и конфиг файл
 * консольные команды перекрывают описаные в кофигурационном файле.
 */

namespace Kruul;
use Kruul\Logger;
use Kruul\Logger\Writer\FileWriter;
use Kruul\Logger\Writer\SymfonyConsoleWriter;
use Kruul\Logger\Writer\ConsoleWriter;
///use Zend\Config;

error_reporting(E_ALL);

class Config{
    private $params;
    private $logger;
    private $config;
    private $container;


    public function __construct(array $params){
        $this->params=$params;
    }

    public function setContainer($container){
        $this->container=$container;
        return $this;
    }

    public function getContainer(){
        return $this->container;
    }

    public function setLogger($logger=null){
        if ($logger) $this->logger=$logger;
        if (!$this->logger) $this->logger=new Logger();
        if (isset($this->params['logfile'])) {
            $this->logger->addWriter(new FileWriter($this->params['logfile']));
        }
        return $this;
    }

    protected function Logger(){
        return $this->logger;
    }

    public function getLogger(){
        return $this->logger;
    }


    public function setSymfonyOut($symfonyout){
        $this->symfonyout=$symfonyout;
        return $this;
    }

    public function setDebug(){ //По умолчанию здесь debug = true
        if ($this->params['debug']){
            if (!$this->logger) $this->logger=new Logger();
            if ($this->symfonyout){
                $this->logger->addWriter(new SymfonyConsoleWriter($this->symfonyout));
            } else {
                $this->logger->addWriter(new ConsoleWriter());
            }
        }
        return $this;
    }

    public function getParam($param){
        return (isset($this->params[$param])) ? $this->params[$param]:null;
    }

    public function getParams(){
        return $this->params;
    }

    protected function setConfig(){
        $this->config=$this->params;
        if ($this->params['config']){

          //parse_ini_file(filename)
            // $reader = new \Zend\Config\Reader\Ini();
            // $reader->setNestSeparator('_');
            // $config= $reader->fromFile($this->params['config']);
            $config=$this->getFromIni($this->params['config']);
            //Приводим к одномерному массиву для сравнения с коммандной строкой
            foreach ($config as $node => $nodevalue) {
                if (is_array($nodevalue)) {
                    foreach ($nodevalue as $key => $value) {
                         $config[$node.$reader->getNestSeparator().$key]=$value;
                    }
                }
            }
            // Приоритет для командной строки
            foreach ($this->params as $key => $value) {
              if (!$value) {
                //if ((isset($config[$key])) && ($config[$key])) $this->params[$key]=$config[$key];
                if ((isset($config[$key])) && ($config[$key])) $this->config[$key]=$config[$key];
              }
            }
       }
        return $this;
    }


    protected function getFromIni($filename){
        if (!is_file($filename) || !is_readable($filename)) {
            throw new Exception\RuntimeException(sprintf("File '%s' doesn't exist or not readable",$filename));
        }
        set_error_handler(
            function ($error, $message = '') use ($filename) {
                throw new Exception\RuntimeException(sprintf('Error reading INI file "%s": %s', $filename, $message),$error);
            },
            E_WARNING
        );
        $ini = parse_ini_file($filename, true);
        restore_error_handler();

        return $ini;
    }

    public function getConfig(){
        if (!$this->config) $this->setConfig();
        return $this->config;
    }

}
