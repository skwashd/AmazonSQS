<?php

/**
 * This file is part of the AmazonSQS package.
 *
 * (c) Christian Eikermann <christian@chrisdev.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\AmazonSQS;

use AmazonSQS\Manager;
use AmazonSQS\Client;
use AmazonSQS\Storage\QueueStorage;
use AmazonSQS\Model\Queue;
use AmazonSQS\Model\Message;

use Symfony\Component\Serializer\Serializer;

/**
 * Test class for Manager.
 * Generated by PHPUnit on 2012-02-25 at 11:02:06.
 */
class ManagerTest extends \PHPUnit_Framework_TestCase
{

    public function dpSetAndGetTest()
    {
        return array(
            array('Client', new Client('blub', 'blub')),
            array('Serializer', new Serializer()),
            array('QueueStorage', new QueueStorage()),
            array('Endpoint', 'sqs.%s.local'),
            array('Region', Manager::REGION_AP_SOUTHEAST_1),
        );
    }

    /**
     * @dataProvider dpSetAndGetTest
     */
    public function testSetAndGet($name, $value)
    {
        $manager = new Manager('blub', 'blub');

        $method = 'set' . $name;
        $manager->$method($value);

        $method = 'get' . $name;
        $this->assertEquals($value, $manager->$method(), 'Wrong value with ' . $name);
    }
    
    public function testGetClient()
    {
        $manager = new \AmazonSQS\Manager('accesskey', 'secretkey');
        $client = $manager->getClient();
        
        $this->assertInstanceOf('\AmazonSQS\Client', $client, 'Client should be an instance of AmazonSQS\Client');
    }
    
    public function testGetSerializer()
    {
        $manager = new \AmazonSQS\Manager('accesskey', 'secretkey');
        $serializer = $manager->getSerializer();
        
        $this->assertInstanceOf('\Symfony\Component\Serializer\Serializer', $serializer, 'Serializer should be an instance of Symfony\Component\Serializer\Serializer');
    }
    
    public function testGetQueueStorage()
    {
        $manager = new \AmazonSQS\Manager('accesskey', 'secretkey');
        $storage = $manager->getQueueStorage();
        
        $this->assertInstanceOf('\AmazonSQS\Storage\QueueStorage', $storage, 'Storage should be an instance of \AmazonSQS\Storage\QueueStorage');
    }
    
    public function testGetUrl()
    {
        $manager = new Manager('blub', 'blub');
        $manager->setRegion('region');
        $manager->setEndpoint('sqs.%s.local');

        $this->assertEquals('https://sqs.region.local', $manager->getUrl(), 'Wrong url');
    }

    public function testGetQueuesEmptyResponse()
    {
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call', 'getQueueByUrl'))
                ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('ListQueues', array())
                ->will($this->returnValue(array()));

        $manager->expects($this->never())
                ->method('getQueueByUrl');

        $result = $manager->getQueues();
        $this->assertEquals(array(), $result, 'Result should be an empty');
    }

    public function testGetQueuesResponseNoArray()
    {
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call', 'getQueueByUrl'))
                ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('ListQueues', array())
                ->will($this->returnValue(array('QueueUrl' => 'blub')));

        $manager->expects($this->never())
                ->method('getQueueByUrl');

        $result = $manager->getQueues();
        $this->assertEquals(array(), $result, 'Result should be an empty');
    }

    public function testGetQueuesOneQueue()
    {
        $queue1 = new Queue();
        $queue1->setUrl('queue1_url');

        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call', 'getQueueByUrl'))
                ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('ListQueues', array())
                ->will($this->returnValue(array('QueueUrl' => array('url1'))));

        $manager->expects($this->once())
                ->method('getQueueByUrl')
                ->with('url1')
                ->will($this->returnValue($queue1));

        $result = $manager->getQueues();
        $this->assertEquals(array($queue1), $result, 'Result not equal');
    }

    public function testGetQueuesTwoQueue()
    {
        $queue1 = new Queue();
        $queue1->setUrl('queue1_url');

        $queue2 = new Queue();
        $queue2->setUrl('queue2_url');

        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call', 'getQueueByUrl'))
                ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('ListQueues', array())
                ->will($this->returnValue(array('QueueUrl' => array('url1', 'url2'))));

        $manager->expects($this->at(1))
                ->method('getQueueByUrl')
                ->with('url1')
                ->will($this->returnValue($queue1));
        $manager->expects($this->at(2))
                ->method('getQueueByUrl')
                ->with('url2')
                ->will($this->returnValue($queue2));

        $result = $manager->getQueues();
        $this->assertEquals(array($queue1, $queue2), $result, 'Result not equal');
    }

    public function testGetQueuesTwoQueueWithNamePrefix()
    {
        $queue1 = new Queue();
        $queue1->setUrl('queue1_url');

        $queue2 = new Queue();
        $queue2->setUrl('queue2_url');

        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call', 'getQueueByUrl'))
                ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('ListQueues', array('QueueNamePrefix' => 'example_prefix'))
                ->will($this->returnValue(array('QueueUrl' => array('url1', 'url2'))));

        $manager->expects($this->at(1))
                ->method('getQueueByUrl')
                ->with('url1')
                ->will($this->returnValue($queue1));
        $manager->expects($this->at(2))
                ->method('getQueueByUrl')
                ->with('url2')
                ->will($this->returnValue($queue2));

        $result = $manager->getQueues('example_prefix');
        $this->assertEquals(array($queue1, $queue2), $result, 'Result not equal');
    }

    public function testGetQueuesTwoQueueWithLoadAttr()
    {
        $queue1 = new Queue();
        $queue1->setUrl('queue1_url');

        $queue2 = new Queue();
        $queue2->setUrl('queue2_url');

        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call', 'getQueueByUrl'))
                ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('ListQueues', array())
                ->will($this->returnValue(array('QueueUrl' => array('url1', 'url2'))));

        $manager->expects($this->at(1))
                ->method('getQueueByUrl')
                ->with('url1', true)
                ->will($this->returnValue($queue1));
        $manager->expects($this->at(2))
                ->method('getQueueByUrl')
                ->with('url2', true)
                ->will($this->returnValue($queue2));

        $result = $manager->getQueues(null, true);
        $this->assertEquals(array($queue1, $queue2), $result, 'Result not equal');
    }

    public function testGetQueuesTwoQueueWithoutLoadAttr()
    {
        $queue1 = new Queue();
        $queue1->setUrl('queue1_url');

        $queue2 = new Queue();
        $queue2->setUrl('queue2_url');

        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call', 'getQueueByUrl'))
                ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('ListQueues', array())
                ->will($this->returnValue(array('QueueUrl' => array('url1', 'url2'))));

        $manager->expects($this->at(1))
                ->method('getQueueByUrl')
                ->with('url1', false)
                ->will($this->returnValue($queue1));
        $manager->expects($this->at(2))
                ->method('getQueueByUrl')
                ->with('url2', false)
                ->will($this->returnValue($queue2));

        $result = $manager->getQueues(null, false);
        $this->assertEquals(array($queue1, $queue2), $result, 'Result not equal');
    }

    public function testGetQueueByNameNoResponse()
    {
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('call', 'getQueueByUrl'))
                        ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('GetQueueUrl', array('QueueName' => 'testqueuename'))
                ->will($this->returnValue(array()));

        $manager->expects($this->never())
                ->method('getQueueByUrl');

        $result = $manager->getQueueByName('testqueuename');
        $this->assertNull($result, 'Result should be null');
    }
    
    public function testGetQueueByNameWithoutLoadAttr()
    {
        $queue1 = new Queue();
        $queue1->setUrl('queue1_url');

        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('call', 'getQueueByUrl'))
                        ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('GetQueueUrl', array('QueueName' => 'testqueuename'))
                ->will($this->returnValue(array('QueueUrl' => 'url1')));

        $manager->expects($this->once())
                ->method('getQueueByUrl')
                ->with('url1', false)
                ->will($this->returnValue($queue1));

        $result = $manager->getQueueByName('testqueuename', false);
        $this->assertEquals($queue1, $result, 'Result not equal');
    }
    
    public function testGetQueueByNameWithLoadAttr()
    {
        $queue1 = new Queue();
        $queue1->setUrl('queue1_url');

        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('call', 'getQueueByUrl'))
                        ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('GetQueueUrl', array('QueueName' => 'testqueuename'))
                ->will($this->returnValue(array('QueueUrl' => 'url1')));

        $manager->expects($this->once())
                ->method('getQueueByUrl')
                ->with('url1', true)
                ->will($this->returnValue($queue1));

        $result = $manager->getQueueByName('testqueuename', true);
        $this->assertEquals($queue1, $result, 'Result not equal');
    }
    
    public function testGetQueueByUrlWithLoadAttr()
    {
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('loadQueueAttributes'))
                        ->getMock();
        $manager->expects($this->once())
                ->method('loadQueueAttributes')
                ->will($this->returnArgument(0));
        
        $queue = $manager->getQueueByUrl('http://test.x/blub', true);
        $this->assertInstanceOf('AmazonSQS\Model\Queue', $queue, 'Queue should be an instance of AmazonSQS\Model\Queue');
        $this->assertEquals('http://test.x/blub', $queue->getUrl(), 'Wrong queue url');
        $this->assertEquals('blub', $queue->getName(), 'Wrong queue name');        
    }
    
    public function testGetQueueByUrlWithoutLoadAttr()
    {
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('loadQueueAttributes'))
                        ->getMock();
        $manager->expects($this->never())
                ->method('loadQueueAttributes');
        
        $queue = $manager->getQueueByUrl('http://test.x/blub', false);
        $this->assertInstanceOf('AmazonSQS\Model\Queue', $queue, 'Queue should be an instance of AmazonSQS\Model\Queue');
        $this->assertEquals('http://test.x/blub', $queue->getUrl(), 'Wrong queue url');
        $this->assertEquals('blub', $queue->getName(), 'Wrong queue name');
    }
    
    public function testCreateQueue()
    {
        $queue = new Queue();
        $queue->setName('testqueuename');

        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('call'))
                        ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('CreateQueue', array('QueueName' => 'testqueuename'))
                ->will($this->returnValue(array('QueueUrl' => 'url1')));
        
        $queue2 = $manager->createQueue($queue);
        $this->assertEquals('url1', $queue2->getUrl(), 'Wrong queue url');
    }

    public function testCreateQueueWithAttributes()
    {
        $queue = new Queue();
        $queue->setName('testqueuename');
        $queue->setDelaySeconds(100);

        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('call'))
                        ->getMock();

        $manager->expects($this->once())
                ->method('call')
                ->with('CreateQueue', array('QueueName' => 'testqueuename', 'Attribute.1.Name' => 'DelaySeconds', 'Attribute.1.Value' => 100))
                ->will($this->returnValue(array('QueueUrl' => 'url1')));
        
        $queue2 = $manager->createQueue($queue);
        $this->assertEquals('url1', $queue2->getUrl(), 'Wrong queue url');
    }
    
    public function testUpdateQueueNotExists()
    {
        $queue = new Queue();
        $queue->setUrl('http://test.x/bla');
        
        $queueStorage = $this->getMockBuilder('AmazonSQS\Storage\QueueStorage')
                             ->getMock();
        $queueStorage->expects($this->once())
                     ->method('exists')
                     ->with($queue)
                     ->will($this->returnValue(false));
        
        $this->setExpectedException('RuntimeException');
        
        $manager = new \AmazonSQS\Manager('accesskey', 'secretkey');
        $manager->setQueueStorage($queueStorage);
        
        $queue = $manager->updateQueue($queue);
    }
    
    public function testUpdateQueueNoChanges()
    {
        $queue = new Queue();
        $queue->setUrl('http://test.x/bla');
        
        $queueStorage = $this->getMockBuilder('AmazonSQS\Storage\QueueStorage')
                             ->getMock();
        $queueStorage->expects($this->once())
                     ->method('exists')
                     ->with($queue)
                     ->will($this->returnValue(true));
        $queueStorage->expects($this->once())
                     ->method('get')
                     ->with($queue)
                     ->will($this->returnValue($queue));
        $queueStorage->expects($this->once())
                     ->method('add')
                     ->with($queue)
                     ->will($this->returnValue(true));
        
        $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
                           ->getMock();
        $serializer->expects($this->exactly(2))
                   ->method('normalize')
                   ->with($queue)
                   ->will($this->returnValue(array('name' => 'test')));
        
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('call'))
                        ->getMock();

        $manager->expects($this->never())
                ->method('call');
        
        $manager->setQueueStorage($queueStorage);
        $manager->setSerializer($serializer);
        
        $queue = $manager->updateQueue($queue);
    }

    public function testUpdateQueueWithChanges()
    {
        $queueOld = new Queue();
        $queueOld->setUrl('http://test.x/bla');
        $queueOld->setDelaySeconds(55);
        $queueOld->setMaximumMessageSize(1024);
        
        $queueNew = clone $queueOld;
        $queueNew->setDelaySeconds(11);
        $queueNew->setMaximumMessageSize(512);
        
        $queueStorage = $this->getMockBuilder('AmazonSQS\Storage\QueueStorage')
                             ->getMock();
        $queueStorage->expects($this->once())
                     ->method('exists')
                     ->with($queueNew)
                     ->will($this->returnValue(true));
        $queueStorage->expects($this->once())
                     ->method('get')
                     ->with($queueNew)
                     ->will($this->returnValue($queueOld));
        $queueStorage->expects($this->once())
                     ->method('add')
                     ->with($queueNew)
                     ->will($this->returnValue(true));
        
        $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
                           ->getMock();
        $serializer->expects($this->at(0))
                   ->method('normalize')
                   ->with($queueNew)
                   ->will($this->returnValue(array('delaySeconds' => '11', 'maximumMessageSize' => '512')));
        $serializer->expects($this->at(1))
                   ->method('normalize')
                   ->with($queueOld)
                   ->will($this->returnValue(array('delaySeconds' => '55', 'maximumMessageSize' => '1024')));
        
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('call'))
                        ->getMock();

        $manager->expects($this->at(0))
                ->method('call')
                ->with('SetQueueAttributes', array('Attribute.Name' => 'DelaySeconds', 'Attribute.Value' => '11'), 'http://test.x/bla')
                ->will($this->returnValue(true));
        
        $manager->expects($this->at(1))
                ->method('call')
                ->with('SetQueueAttributes', array('Attribute.Name' => 'MaximumMessageSize', 'Attribute.Value' => '512'), 'http://test.x/bla')
                ->will($this->returnValue(true));
        
        $manager->setQueueStorage($queueStorage);
        $manager->setSerializer($serializer);
        
        $queue = $manager->updateQueue($queueNew);
    }
    
    public function testDeleteQueueNotSuccess()
    {
        $queue = new Queue();
        $queue->setUrl('http://test.x/blub');
        
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('call'))
                        ->getMock();
        
        $manager->expects($this->once())
                ->method('call')
                ->with('DeleteQueue', array(), 'http://test.x/blub')
                ->will($this->returnValue(false));
        
        $queueStorage = $this->getMockBuilder('AmazonSQS\Storage\QueueStorage')
                             ->getMock();
        $queueStorage->expects($this->never())
                     ->method('remove');
        
        $manager->setQueueStorage($queueStorage);
        
        $this->assertFalse($manager->deleteQueue($queue), 'DeleteQueue should be return false');
    }
    
    public function testDeleteQueueSuccess()
    {
        $queue = new Queue();
        $queue->setUrl('http://test.x/blub');
        
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                        ->setConstructorArgs(array('accesskey', 'secretkey'))
                        ->setMethods(array('call'))
                        ->getMock();
        
        $manager->expects($this->once())
                ->method('call')
                ->with('DeleteQueue', array(), 'http://test.x/blub')
                ->will($this->returnValue(true));
        
        $queueStorage = $this->getMockBuilder('AmazonSQS\Storage\QueueStorage')
                             ->getMock();
        $queueStorage->expects($this->once())
                     ->method('remove')
                     ->with($queue);
        
        $manager->setQueueStorage($queueStorage);
        
        $this->assertTrue($manager->deleteQueue($queue), 'DeleteQueue should be return true');
    }
    
    public function testSendMessageFailed()
    {
        $queue = new Queue();
        $queue->setUrl('http://test.x/blub');
        
        $message = new Message();
        $message->setBody('Example body');
        
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call'))
                ->getMock();
        
        $manager->expects($this->once())
                ->method('call')
                ->with('SendMessage', array('MessageBody' => 'Example body'), 'http://test.x/blub')
                ->will($this->returnValue(array()));
        
        $this->assertFalse($manager->sendMessage($queue, $message), 'SendMessage should be return false');
    }
    
    public function testSendMessage()
    {
        $queue = new Queue();
        $queue->setUrl('http://test.x/blub');
        
        $message = new Message();
        $message->setBody('Example body');
        
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call'))
                ->getMock();
        
        $manager->expects($this->once())
                ->method('call')
                ->with('SendMessage', array('MessageBody' => 'Example body'), 'http://test.x/blub')
                ->will($this->returnValue(array('MessageId' => 'SomeId')));
        
        $this->assertTrue($manager->sendMessage($queue, $message), 'SendMessage should be return true');
        $this->assertEquals('SomeId', $message->getMessageId(), 'Wrong message id');
    }
    
    public function testReceiveMessageWithVisibilityTimeout()
    {
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call'))
                ->getMock();
        
        $manager->expects($this->once())
                ->method('call')
                ->with('ReceiveMessage', array('VisibilityTimeout' => 123), 'http://test.x/blub')
                ->will($this->returnValue(array()));
        
        $queue = new Queue();
        $queue->setUrl('http://test.x/blub');
        
        $this->assertNull($manager->receiveMessage($queue, 123), 'ReceiveMessage should be return null');
    }
    
    public function testReceiveMessageWithLoadMessageAttr()
    {
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call'))
                ->getMock();
        
        $manager->expects($this->once())
                ->method('call')
                ->with('ReceiveMessage', array('AttributeName.1' => 'All'), 'http://test.x/blub')
                ->will($this->returnValue(array()));
        
        $queue = new Queue();
        $queue->setUrl('http://test.x/blub');
        
        $this->assertNull($manager->receiveMessage($queue, null, true), 'ReceiveMessage should be return null');
    }
    
    public function testReceiveMessage()
    {
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call'))
                ->getMock();
        
        $manager->expects($this->once())
                ->method('call')
                ->with('ReceiveMessage', array(), 'http://test.x/blub')
                ->will($this->returnValue(array('Message' => array('Body' => 'Example Body', 'Attribute' => array(array('Name' => 'SenderId', 'Value' => '123'))))));
        
        $queue = new Queue();
        $queue->setUrl('http://test.x/blub');
        
        $data = array();
        $data['Body'] = 'Example Body';
        $data['senderId'] = '123';
        
        $message = new Message();
        
        $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
                           ->getMock();
        $serializer->expects($this->at(0))
                   ->method('denormalize')
                   ->with($data, '\AmazonSQS\Model\Message')
                   ->will($this->returnValue($message));
        
        $manager->setSerializer($serializer);
        
        $message = $manager->receiveMessage($queue);
        $this->assertInstanceOf('AmazonSQS\Model\Message', $message, 'Message should be an instance of Message');
        $this->assertEquals($queue, $message->getQueue(), 'Wrong queue in message');
    }
    
    public function testDeleteMessage()
    {
        $queue = new Queue();
        $queue->setUrl('http://test.x/blub');
        
        $message = new Message();
        $message->setReceiptHandle('example_receipt_handle');
        $message->setQueue($queue);
        
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call'))
                ->getMock();
        
        $manager->expects($this->once())
                ->method('call')
                ->with('DeleteMessage', array('ReceiptHandle' => 'example_receipt_handle'), 'http://test.x/blub')
                ->will($this->returnValue(true));
     
        $this->assertTrue($manager->deleteMessage($message), 'DeleteMessage should return false');
    }
    
    public function testLoadQueueAttributes()
    {
        $queue = new Queue();
        $queue->setUrl('http://test.x/blub');
        $queue->setDelaySeconds(123);
        
        $queue2 = new Queue();
        $queue2->setUrl('http://test.x/blub');
        $queue2->setDelaySeconds(51);
        
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('call'))
                ->getMock();
        
        $manager->expects($this->once())
                ->method('call')
                ->with('GetQueueAttributes', array('AttributeName.1' => 'All'), 'http://test.x/blub')
                ->will($this->returnValue(array('Attribute' => array(array('Name' => 'DelaySeconds', 'Value' => '51')))));
        
        $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
                           ->getMock();
        $serializer->expects($this->at(0))
                   ->method('normalize')
                   ->with($queue)
                   ->will($this->returnValue(array('delaySeconds' => '123')));
        $serializer->expects($this->at(1))
                   ->method('denormalize')
                   ->with(array('delaySeconds' => '51'), '\AmazonSQS\Model\Queue')
                   ->will($this->returnValue($queue2));
        
        $queueStorage = $this->getMockBuilder('AmazonSQS\Storage\QueueStorage')
                             ->getMock();
        $queueStorage->expects($this->once())
                     ->method('add')
                     ->with($queue2);
        
        $manager->setQueueStorage($queueStorage);
        $manager->setSerializer($serializer);
        
        $queue = $manager->loadQueueAttributes($queue);
        $this->assertEquals(51, $queue->getDelaySeconds(), 'Wrong queue');
    }
    
    public function testCallError()
    {
        $xml = '<?xml version="1.0"?><root><Error><Message>Error Message</Message></Error></root>';
        
        $response = new \apiTalk\Response($xml);
        $client = $this->getMockBuilder('AmazonSQS\Client')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->getMock();
        
        $client->expects($this->once())
               ->method('post')
               ->with('http://test.x/blub', array('SampleName' => 'SampleValue', 'Action' => 'ExampleAction'))
               ->will($this->returnValue($response));
        
        $manager = new \AmazonSQS\Manager('accesskey', 'secretkey');
        $manager->setClient($client);
        
        $this->setExpectedException('RuntimeException');
        
        $manager->call('ExampleAction', array('SampleName' => 'SampleValue'), 'http://test.x/blub');
    }
    
    public function testCall()
    {
        $xml = '<?xml version="1.0"?><root><ExampleActionResult><Message>Value</Message></ExampleActionResult></root>';
        
        $response = new \apiTalk\Response($xml);
        $client = $this->getMockBuilder('AmazonSQS\Client')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->getMock();
        
        $client->expects($this->once())
               ->method('post')
               ->with('http://test.x/blub', array('SampleName' => 'SampleValue', 'Action' => 'ExampleAction'))
               ->will($this->returnValue($response));
        
        $manager = new \AmazonSQS\Manager('accesskey', 'secretkey');
        $manager->setClient($client);
        
        $response = $manager->call('ExampleAction', array('SampleName' => 'SampleValue'), 'http://test.x/blub');
        $this->assertEquals(array('Message' => 'Value'), $response, 'Wrong response');
    }
    
    public function testCallWithoutResult()
    {
        $xml = '<?xml version="1.0"?><root></root>';
        
        $response = new \apiTalk\Response($xml);
        $client = $this->getMockBuilder('AmazonSQS\Client')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->getMock();
        
        $client->expects($this->once())
               ->method('post')
               ->with('http://test.x/blub', array('SampleName' => 'SampleValue', 'Action' => 'ExampleAction'))
               ->will($this->returnValue($response));
        
        $manager = new \AmazonSQS\Manager('accesskey', 'secretkey');
        $manager->setClient($client);
        
        $this->assertTrue($manager->call('ExampleAction', array('SampleName' => 'SampleValue'), 'http://test.x/blub'), 'Call should be return true');
    }
    
    public function testCallWithoutUrl()
    {
        $xml = '<?xml version="1.0"?><root><ExampleActionResult><Message>Value</Message></ExampleActionResult></root>';
        
        $response = new \apiTalk\Response($xml);
        $client = $this->getMockBuilder('AmazonSQS\Client')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->getMock();
        
        $client->expects($this->once())
               ->method('post')
               ->with('http://test.x/blub', array('SampleName' => 'SampleValue', 'Action' => 'ExampleAction'))
               ->will($this->returnValue($response));
        
        $manager = $this->getMockBuilder('AmazonSQS\Manager')
                ->setConstructorArgs(array('accesskey', 'secretkey'))
                ->setMethods(array('getUrl'))
                ->getMock();
        
        $manager->expects($this->once())
                ->method('getUrl')
                ->will($this->returnValue('http://test.x/blub'));
        
        $manager->setClient($client);
        
        $response = $manager->call('ExampleAction', array('SampleName' => 'SampleValue'));
        $this->assertEquals(array('Message' => 'Value'), $response, 'Wrong response');
    }
    
}

?>