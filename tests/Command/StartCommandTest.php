<?php

/*
 * This file is part of the Certificationy CLI application.
 *
 * (c) Vincent Composieux <vincent.composieux@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Command;

use Certificationy\Loaders\YamlLoader as Loader;
use Certificationy\Cli\Command\StartCommand;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * StartCommandTest
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class StartCommandTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var StartCommand
     */
    private $command;

    /**
     * @var string config filepath
     */
    private $configFile;

    /**
     * @var Loader
     */
    private $yamlLoader;

    public function setUp()
    {
        $app = new Application();
        $app->add(new StartCommand());
        $this->command = $app->find('start');
        $this->configFile = $this->getTestsFolder() . 'config_test.yml';
        $paths = Yaml::parse(file_get_contents($this->configFile));

        $this->yamlLoader = new Loader($paths);
    }

    public function testCanListCategories()
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array(
            'command' => $this->command->getName(),
            '-l' => true,
            '-c' => $this->configFile
        ));

        $output = $commandTester->getDisplay();

        $this->assertRegExp('/A/', $output);
        $this->assertCount(count($this->yamlLoader->categories()) + 1, explode("\n", $output));
    }

    public function testCanGetQuestions()
    {
        $helper = $this->command->getHelper('question');
        $helper->setInputStream($this->getInputStream(str_repeat("0\n", 20)));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array(
            'command' => $this->command->getName(),
            'categories' => ['B'],
            '-c' => $this->configFile
        ));

        $output = $commandTester->getDisplay();
        $this->assertRegExp('/B/', $output);
        $this->assertRegExp('/Starting a new set of 3 questions/', $commandTester->getDisplay());
    }

    public function testCanHideInformationAboutMultipleChoice()
    {
        $helper = $this->command->getHelper('question');
        $helper->setInputStream($this->getInputStream(str_repeat("0\n", 1)));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array(
            'command' => $this->command->getName(),
            '--hide-multiple-choice' => null,
            '--number' => 1,
            '-c' => $this->configFile
        ));

        $output = $commandTester->getDisplay();
        $this->assertNotRegExp('/This question IS( NOT)? multiple choice/', $output);
    }

    public function testCanUseTrainingMode()
    {
        $helper = $this->command->getHelper('question');
        $helper->setInputStream($this->getInputStream(str_repeat("0\n", 1)));

        $commandTester = new CommandTester($this->command);
        $commandTester->execute(array(
            'command' => $this->command->getName(),
            '--hide-multiple-choice' => null,
            '--number' => 1,
            '--training' => true,
            '-c' => $this->configFile
        ));

        $commandTester->setInputs([0]);
        $output = $commandTester->getDisplay();

        $this->assertRegExp('/| Question | Correct answer | Result | Help |/', $output);
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }

    private function getTestsFolder()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
    }
}
