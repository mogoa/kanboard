<?php

require_once __DIR__.'/Base.php';

use Model\Project;
use Model\User;
use Model\TaskFilter;
use Model\TaskCreation;
use Model\DateParser;

class TaskFilterTest extends Base
{
    public function testSearchWithEmptyResult()
    {
        $dp = new DateParser($this->container);
        $p = new Project($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFilter($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test')));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'my task title is awesome', 'date_due' => $dp->getTimestampFromIsoFormat('-2 days'))));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'my task title is amazing', 'date_due' => $dp->getTimestampFromIsoFormat('+1 day'))));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'Bob at work', 'date_due' => $dp->getTimestampFromIsoFormat('-1 day'))));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'youpi', 'date_due' => $dp->getTimestampFromIsoFormat(time()))));

        $this->assertEmpty($tf->search('search something')->findAll());
    }

    public function testSearchWithDueDate()
    {
        $dp = new DateParser($this->container);
        $p = new Project($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFilter($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test')));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'my task title is awesome', 'date_due' => $dp->getTimestampFromIsoFormat('-2 days'))));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'my task title is amazing', 'date_due' => $dp->getTimestampFromIsoFormat('+1 day'))));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'Bob at work', 'date_due' => $dp->getTimestampFromIsoFormat('-1 day'))));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'youpi', 'date_due' => $dp->getTimestampFromIsoFormat(time()))));

        $tf->search('due:>'.date('Y-m-d'));
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(1, $tasks);
        $this->assertEquals('my task title is amazing', $tasks[0]['title']);

        $tf->search('due:>='.date('Y-m-d'));
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(2, $tasks);
        $this->assertEquals('my task title is amazing', $tasks[0]['title']);
        $this->assertEquals('youpi', $tasks[1]['title']);

        $tf->search('due:<'.date('Y-m-d'));
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(2, $tasks);
        $this->assertEquals('my task title is awesome', $tasks[0]['title']);
        $this->assertEquals('Bob at work', $tasks[1]['title']);

        $tf->search('due:<='.date('Y-m-d'));
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(3, $tasks);
        $this->assertEquals('my task title is awesome', $tasks[0]['title']);
        $this->assertEquals('Bob at work', $tasks[1]['title']);
        $this->assertEquals('youpi', $tasks[2]['title']);

        $tf->search('due:tomorrow');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(1, $tasks);
        $this->assertEquals('my task title is amazing', $tasks[0]['title']);

        $tf->search('due:yesterday');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(1, $tasks);
        $this->assertEquals('Bob at work', $tasks[0]['title']);

        $tf->search('due:today');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(1, $tasks);
        $this->assertEquals('youpi', $tasks[0]['title']);
    }

    public function testSearchWithColor()
    {
        $p = new Project($this->container);
        $u = new User($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFilter($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test')));
        $this->assertEquals(2, $u->create(array('username' => 'bob', 'name' => 'Bob Ryan')));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'my task title is awesome', 'color_id' => 'light_green')));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'my task title is amazing', 'color_id' => 'blue')));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'Bob at work')));

        $tf->search('color:"Light Green"');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(1, $tasks);
        $this->assertEquals('my task title is awesome', $tasks[0]['title']);

        $tf->search('color:"Light Green" amazing');
        $tasks = $tf->findAll();
        $this->assertEmpty($tasks);

        $tf->search('color:"plop');
        $tasks = $tf->findAll();
        $this->assertEmpty($tasks);

        $tf->search('color:unknown');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(3, $tasks);

        $tf->search('color:blue amazing');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(1, $tasks);
        $this->assertEquals('my task title is amazing', $tasks[0]['title']);

        $tf->search('color:blue color:Yellow');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(2, $tasks);
        $this->assertEquals('my task title is amazing', $tasks[0]['title']);
        $this->assertEquals('Bob at work', $tasks[1]['title']);
    }

    public function testSearchWithAssignee()
    {
        $p = new Project($this->container);
        $u = new User($this->container);
        $tc = new TaskCreation($this->container);
        $tf = new TaskFilter($this->container);

        $this->assertEquals(1, $p->create(array('name' => 'test')));
        $this->assertEquals(2, $u->create(array('username' => 'bob', 'name' => 'Bob Ryan')));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'my task title is awesome', 'owner_id' => 1)));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'my task title is amazing', 'owner_id' => 0)));
        $this->assertNotFalse($tc->create(array('project_id' => 1, 'title' => 'Bob at work', 'owner_id' => 2)));

        $tf->search('assignee:john');
        $tasks = $tf->findAll();
        $this->assertEmpty($tasks);

        $tf->search('assignee:admin my task title');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(1, $tasks);
        $this->assertEquals('my task title is awesome', $tasks[0]['title']);

        $tf->search('my task title');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(2, $tasks);
        $this->assertEquals('my task title is awesome', $tasks[0]['title']);
        $this->assertEquals('my task title is amazing', $tasks[1]['title']);

        $tf->search('my task title assignee:nobody');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(1, $tasks);
        $this->assertEquals('my task title is amazing', $tasks[0]['title']);

        $tf->search('assignee:"Bob ryan" assignee:nobody');
        $tasks = $tf->findAll();
        $this->assertNotEmpty($tasks);
        $this->assertCount(2, $tasks);
        $this->assertEquals('my task title is amazing', $tasks[0]['title']);
        $this->assertEquals('Bob at work', $tasks[1]['title']);
    }

    public function testCopy()
    {
        $tf = new TaskFilter($this->container);
        $filter1 = $tf->create();
        $filter2 = $tf->copy();

        $this->assertTrue($filter1 !== $filter2);
        $this->assertTrue($filter1->query !== $filter2->query);
        $this->assertTrue($filter1->query->condition !== $filter2->query->condition);
    }
}
