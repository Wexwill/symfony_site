<?php

namespace App\Tests\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Panther\PantherTestCase;

class ConferenceControllerTest extends PantherTestCase
{
    public function testIndex()
    {
        $client = static::createPantherClient(['external_base_uri' => rtrim($_SERVER['SYMFONY_PROJECT_DEFAULT_ROUTE_URL'], '/')]);
        $client->request('GET', '/en/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Give your feedback');
    }

    public function testConferencePage()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');

        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('div:contains("There are 1 comments")');
    }

    public function testCommentSubmission()
    {
        $client = static::createClient();
        $client->request('GET', '/en/conference/minsk-2001');
        $client->submitForm('submit', [
            'comment_form[author]' => 'Fabien',
            'comment_form[text]' => 'Some feedback from an automated functional test',
            'comment_form[email]' => $email = 'me@automat.ed',
            'comment_form[photo]' => dirname(__DIR__, 2).'/public/images/image.jpg',
        ]);
        $this->assertResponseRedirects();

        // Simulate comment validation.
        $comment = self::getContainer()->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState('published');
        self::getContainer()->get(EntityManagerInterface::class)->flush();


        $client->followRedirect();
        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }

}