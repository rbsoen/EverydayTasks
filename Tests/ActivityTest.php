<?php
require_once 'Models/Activity.php';

use EverydayTasks\Activity;
use EverydayTasks\Util;
use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    private Activity $activity;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->activity =
            new Activity(
                Util::$db,
                "1a",
                "Test activity",
                "My description",
                new DateTime(),
                null
            );
    }

    public function test_creation_of_activity()
    {
        $this->assertInstanceOf(Activity::class, $this->activity);
    }

    public function test_adding_to_database()
    {
        $this->activity->addToDatabase();
        $this->assertNotNull(
            Activity::searchById(Util::$db, "1a"),
            "Create activity in PHP and add to DB"
        );
    }

    public function test_sanitized_setting_a_subject()
    {
        $this->activity->setSubject('<hello>>hello');
        $this->assertEquals(
            "&gt;hello",
            $this->activity->getSubject()
        );
    }

    public function test_sanitized_setting_a_description()
    {
        $this->activity->setDescription('<b>My text here</b>');
        $this->assertEquals(
            "My text here",
            $this->activity->getDescription()
        );
    }

    // TODO: add more tests
}