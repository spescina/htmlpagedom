<?php
namespace Wa72\HtmlPageDom\Tests;

use Wa72\HtmlPageDom\HtmlPageCrawler;

class HtmlPageCrawlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::__construct
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::filter
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::getFirstNode
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::nodeName
     */
    public function testHtmlPageCrawler()
    {
        $c = new HtmlPageCrawler;
        $c->addHtmlContent('<html><body><div id="content"><h1>Title</h1></div></body></html>');
        $title = $c->filter('#content > h1');

        $this->assertInstanceOf('\Wa72\HtmlPageDom\HtmlPageCrawler', $title);
        $this->assertInstanceOf('\DOMNode', $title->getFirstNode());
        $this->assertEquals('h1', $title->nodeName());
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::getInnerHtml
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::setInnerHtml
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::prepend
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::makeEmpty
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::setAttribute
     */
    public function testManipulationFunctions()
    {
        $c = new HtmlPageCrawler;
        $c->addHtmlContent('<html><body><div id="content"><h1>Title</h1></div></body></html>');

        $content = $c->filter('#content');
        $content->append('<p>Das ist ein Testabsatz');

        $this->assertEquals('<h1>Title</h1><p>Das ist ein Testabsatz</p>', $content->getInnerHtml());

        $content->setInnerHtml('<p>Ein neuer <b>Inhalt</p>');
        $this->assertEquals('<p>Ein neuer <b>Inhalt</b></p>', $content->getInnerHtml());

        $content->prepend('<h1>Neue Überschrift');
        $this->assertEquals('<h1>Neue &Uuml;berschrift</h1><p>Ein neuer <b>Inhalt</b></p>', $content->getInnerHtml());

        $h1 = $content->filter('h1');
        $this->assertEquals('Neue Überschrift', $h1->text());

        $b = $content->filter('b');
        $this->assertEquals('Inhalt', $b->text());

        $b2 = $c->filter('#content p b');
        $this->assertEquals('Inhalt', $b2->text());

        $content->append('<p class="a2">Zweiter Absatz</p>');
        $content->append('<p class="a3"><b>Dritter Absatz</b> und noch mehr Text</p>');

        $a3 = $content->filter('p.a3');
        $this->assertEquals('<b>Dritter Absatz</b> und noch mehr Text', $a3->getInnerHtml());

        $a3b = $a3->filter('b');
        $this->assertEquals('Dritter Absatz', $a3b->text());

        $body = $c->filter('body');
        $this->assertEquals('<div id="content"><h1>Neue &Uuml;berschrift</h1><p>Ein neuer <b>Inhalt</b></p><p class="a2">Zweiter Absatz</p><p class="a3"><b>Dritter Absatz</b> und noch mehr Text</p></div>', $body->getInnerHtml());

        $paragraphs = $c->filter('p');
        $this->assertEquals(3, count($paragraphs));

        $paragraphs->append('<span class="appended">.</span>');
        $this->assertEquals('<p>Ein neuer <b>Inhalt</b><span class="appended">.</span></p><p class="a2">Zweiter Absatz<span class="appended">.</span></p><p class="a3"><b>Dritter Absatz</b> und noch mehr Text<span class="appended">.</span></p>', $c->filter('p')->saveHTML());

        $body->makeEmpty();
        $this->assertEmpty($body->getInnerHtml());

        $body->setAttribute('class', 'mybodyclass');
        $this->assertEquals('mybodyclass', $body->attr('class'));

    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::append
     */
    public function testAppend()
    {
        // Testing append string to several elements
        $c = new HtmlPageCrawler('<p>Paragraph 1</p><p>Paragraph 2</p><p>Paragraph 3</p>');
        $c->filter('p')->append('<br>Appended Text');
        $this->assertEquals('<p>Paragraph 1<br>Appended Text</p><p>Paragraph 2<br>Appended Text</p><p>Paragraph 3<br>Appended Text</p>', $c->saveHTML());

        // Testing append HtmlPageCrawler to several elements
        $c = new HtmlPageCrawler('<p>Paragraph 1</p><p>Paragraph 2</p><p>Paragraph 3</p>');
        $c->filter('p')->append(new HtmlPageCrawler('<br>Appended Text'));
        $this->assertEquals('<p>Paragraph 1<br>Appended Text</p><p>Paragraph 2<br>Appended Text</p><p>Paragraph 3<br>Appended Text</p>', $c->saveHTML());

        // Testing append DOMNode to several elements
        $c = new HtmlPageCrawler('<p>Paragraph 1</p><p>Paragraph 2</p><p>Paragraph 3</p>');
        $app = $c->getDOMDocument()->createElement('span', 'Appended Text');
        $c->filter('p')->append($app);
        $this->assertEquals('<p>Paragraph 1<span>Appended Text</span></p><p>Paragraph 2<span>Appended Text</span></p><p>Paragraph 3<span>Appended Text</span></p>', $c->saveHTML());

    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::isHtmlDocument
     */
    public function testIsHtmlDocument()
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<!DOCTYPE html><html><body><div id="content"><h1>Title</h1></div></body></html>');
        $c = new HtmlPageCrawler($dom);

        $this->assertTrue($c->isHtmlDocument());

        $t = $c->filter('body');
        $this->assertFalse($t->isHtmlDocument());

        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $this->assertFalse($c->isHtmlDocument());

        $c = new HtmlPageCrawler('<html><body><div id="content"><h1>Title</h1></div></body></html>');
        $this->assertTrue($c->isHtmlDocument());

    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::saveHTML
     */
    public function testSaveHTML()
    {
        $html = "<!DOCTYPE html>\n<html><body><h1>Title</h1><p>Paragraph 1</p><p>Paragraph 2</p></body></html>\n";
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML($html);
        $c = new HtmlPageCrawler($dom);
        $this->assertEquals($html, $c->saveHTML());
        $ps = $c->filter('p');
        $this->assertEquals('<p>Paragraph 1</p><p>Paragraph 2</p>', $ps->saveHTML());
        $t = $c->filter('h1');
        $this->assertEquals('<h1>Title</h1>', $t->saveHTML());

        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $this->assertEquals('<div id="content"><h1>Title</h1></div>', $c->saveHTML());
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::css
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::getStyle
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::setStyle
     */
    public function testCss()
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<!DOCTYPE html><html><body><div id="content"><h1 style=" margin-top:
         10px;border-bottom:  1px solid red">Title</h1></div></body></html>');
        $c = new HtmlPageCrawler($dom);
        $t = $c->filter('h1');
        $this->assertEquals('10px', $t->css('margin-top'));
        $this->assertEquals('1px solid red', $t->css('border-bottom'));
        $t->css('margin-bottom', '20px');
        $this->assertEquals('20px', $t->css('margin-bottom'));
        $this->assertEquals('10px', $t->getStyle('margin-top'));
        $this->assertEquals('<h1 style="margin-top: 10px;border-bottom: 1px solid red;margin-bottom: 20px;">Title</h1>', $t->saveHTML());
        $t->setStyle('border-bottom', '');
        $this->assertEquals('<h1 style="margin-top: 10px;margin-bottom: 20px;">Title</h1>', $t->saveHTML());
        $t->setStyle('padding-top', '0');
        $this->assertEquals('<h1 style="margin-top: 10px;margin-bottom: 20px;padding-top: 0;">Title</h1>', $t->saveHTML());
        $this->assertEquals('0', $t->getStyle('padding-top'));
        $this->assertNull($t->getStyle('border-bottom'));
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::addClass
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::removeClass
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::hasClass
     */
    public function testClasses()
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<!DOCTYPE html><html><body><div id="content"><h1>Title</h1></div></body></html>');
        $c = new HtmlPageCrawler($dom);
        $t = $c->filter('h1');
        $t->addClass('ueberschrift');
        $t->addClass('nochneklasse');
        $this->assertEquals('<h1 class="ueberschrift nochneklasse">Title</h1>', $t->saveHTML());
        $this->assertTrue($t->hasClass('ueberschrift'));
        $this->assertTrue($t->hasClass('nochneklasse'));
        $t->removeClass('nochneklasse');
        $this->assertTrue($t->hasClass('ueberschrift'));
        $this->assertFalse($t->hasClass('nochneklasse'));
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::addContent
     */
    public function testAddContent()
    {
        $c = new HtmlPageCrawler();
        $c->addContent('<html><body><div id="content"><h1>Title</h1></div></body>');
        $this->assertEquals('<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">'
            . "\n" . '<html><body><div id="content"><h1>Title</h1></div></body></html>' . "\n", $c->saveHTML());

        $c = new HtmlPageCrawler();
        $c->addContent('<div id="content"><h1>Title');
        $this->assertEquals('<div id="content"><h1>Title</h1></div>', $c->saveHTML());

        $c = new HtmlPageCrawler();
        $c->addContent('<p>asdf<p>asdfaf</p>');
        $this->assertEquals(2, count($c));
        $this->assertEquals('<p>asdf</p><p>asdfaf</p>', $c->saveHTML());

    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::before
     */
    public function testBefore()
    {
        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $c->filter('h1')->before('<p>Text before h1</p>');
        $this->assertEquals('<div id="content"><p>Text before h1</p><h1>Title</h1></div>', $c->saveHTML());

        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $c->filter('h1')->before(new HtmlPageCrawler('<p>Text before h1</p><p>and more text before</p>'));
        $this->assertEquals('<div id="content"><p>Text before h1</p><p>and more text before</p><h1>Title</h1></div>', $c->saveHTML());
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::after
     */
    public function testAfter()
    {
        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $c->filter('h1')->after('<p>Text after h1</p>');
        $this->assertEquals('<div id="content"><h1>Title</h1><p>Text after h1</p></div>', $c->saveHTML());

        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $c->filter('h1')->after(new HtmlPageCrawler('<p>Text after h1</p><p>and more text after</p>'));
        $this->assertEquals('<div id="content"><h1>Title</h1><p>Text after h1</p><p>and more text after</p></div>', $c->saveHTML());
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::prepend
     */
    public function testPrepend()
    {
        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $c->filter('#content')->prepend('<p>Text before h1</p>');
        $this->assertEquals('<div id="content"><p>Text before h1</p><h1>Title</h1></div>', $c->saveHTML());

        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $c->filter('#content')->prepend(new HtmlPageCrawler('<p>Text before h1</p><p>and more text before</p>'));
        $this->assertEquals('<div id="content"><p>Text before h1</p><p>and more text before</p><h1>Title</h1></div>', $c->saveHTML());
    }


    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::wrap
     */
    public function testWrap()
    {
        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $c->filter('h1')->wrap('<div class="innercontent">');
        $this->assertEquals('<div id="content"><div class="innercontent"><h1>Title</h1></div></div>', $c->saveHTML());

        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $c->filter('h1')->wrap('<div class="ic">asdf<div class="a1"><div class="a2"></div></div></div></div>');
        $this->assertEquals('<div id="content"><div class="ic">asdf<div class="a1"><div class="a2"><h1>Title</h1></div></div></div></div>', $c->saveHTML());

        $c = new HtmlPageCrawler('<div id="content"><h1>Title</h1></div>');
        $c->filter('h1')->wrap('<div class="ic">asdf</div><div>jkl</div>'); // wrap has more than 1 root element
        $this->assertEquals('<div id="content"><div class="ic">asdf<h1>Title</h1></div></div>', $c->saveHTML()); // only first element is used

        $c = new HtmlPageCrawler('plain text node');
        $c->wrap('<div class="ic"></div>');
        $this->assertEquals('<div class="ic">plain text node</div>', $c->parents()->eq(0)->saveHTML());

        $c = HtmlPageCrawler::create('<div>');
        $m = HtmlPageCrawler::create('message 1')->appendTo($c);
        $m->wrap('<p>');
        $m = HtmlPageCrawler::create('message 2')->appendTo($c);
        $m->wrap('<p>');
        $this->assertEquals('<div><p>message 1</p><p>message 2</p></div>', $c->saveHTML());
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::replaceWith
     */
    public function testReplaceWith()
    {
        $c = HtmlPageCrawler::create('<div id="content"><p>Absatz 1</p><p>Absatz 2</p><p>Absatz 3</p></div>');
        $oldparagraphs = $c->filter('p')->replaceWith('<div>newtext 1</div><div>newtext 2</div>');
        $this->assertEquals('<div id="content"><div>newtext 1</div><div>newtext 2</div><div>newtext 1</div><div>newtext 2</div><div>newtext 1</div><div>newtext 2</div></div>', $c->saveHTML());
        $this->assertEquals('<p>Absatz 1</p><p>Absatz 2</p><p>Absatz 3</p>', $oldparagraphs->saveHTML());
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::replaceAll
     */
    public function testReplaceAll()
    {
        $c = HtmlPageCrawler::create('<div id="content"><p>Absatz 1</p><p>Absatz 2</p><p>Absatz 3</p></div>');
        $new = HtmlPageCrawler::create('<div>newtext 1</div><div>newtext 2</div>');
        $new->replaceAll($c->filter('p'));
        $this->assertEquals('<div id="content"><div>newtext 1</div><div>newtext 2</div><div>newtext 1</div><div>newtext 2</div><div>newtext 1</div><div>newtext 2</div></div>', $c->saveHTML());

    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::wrapAll
     */
    public function testWrapAll()
    {
        $c = HtmlPageCrawler::create('<div id="content"><p>Absatz 1</p><p>Absatz 2</p><p>Absatz 3</p></div>');
        $c->filter('p')->wrapAll('<div class="a">');
        $this->assertEquals('<div id="content"><div class="a"><p>Absatz 1</p><p>Absatz 2</p><p>Absatz 3</p></div></div>', $c->saveHTML());
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::wrapInner
     */
    public function testWrapInner()
    {
        $c = HtmlPageCrawler::create('<div id="content"><p>Absatz 1</p><p>Absatz 2</p><p>Absatz 3</p></div>');
        $c->wrapInner('<div class="a">');
        $this->assertEquals('<div id="content"><div class="a"><p>Absatz 1</p><p>Absatz 2</p><p>Absatz 3</p></div></div>', $c->saveHTML());
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::unwrap
     */
    public function testUnwrap()
    {
        $c = HtmlPageCrawler::create('<div id="content"><div class="a"><p>Absatz 1</p></div></div>');
        $p = $c->filter('p');
        $p->unwrap();
        $this->assertEquals('<div id="content"><p>Absatz 1</p></div>', $c->saveHTML());
    }

    /**
     * @covers Wa72\HtmlPageDom\HtmlPageCrawler::toggleClass
     */
    public function testToggleClass()
    {
        $c = HtmlPageCrawler::create('<div id="1" class="a c"><div id="2" class="b c"></div></div>');
        $c->filter('div')->toggleClass('a d')->toggleClass('b');
        $this->assertEquals('<div id="1" class="c d b"><div id="2" class="c a d"></div></div>', $c->saveHTML());
    }

    public function testRemove()
    {
        // remove every third td in tbody
        $html = <<<END
<table>
    <thead>
    <tr>
        <th>A</th>
        <th>B</th>
    </tr>
    </thead>
    <tbody>
    <tr class="r1">
        <td class="c11">16.12.2013</td>
        <td class="c12">asdf asdf</td>
        <td class="c13">&nbsp;</td>
    </tr>
    <tr class="r2">
        <td class="c21">02.12.2013 16:30</td>
        <td class="c22">asdf asdf</td>
        <td class="c23">&nbsp;</td>
    </tr>
    <tr class="r3">
        <td class="c31">25.11.2013 16:30</td>
        <td class="c32">asdf asdf</td>
        <td class="c33">&nbsp;</td>
    </tr>
    <tr class="r4">
        <td class="c41">18.11.2013 16:30</td>
        <td class="c42">asdf asdf</td>
        <td class="c43">&nbsp;</td>
    </tr>
    <tr class="r5">
        <td class="c51">24.10.2013 16:30</td>
        <td class="c52">asdf asdf</td>
        <td class="c53">&nbsp;</td>
    </tr>
    <tr class="r6">
        <td class="c61">10.10.2013 16:30</td>
        <td class="c62">asdf asdf</td>
        <td class="c63">&nbsp;</td>
    </tr>
</table>
END;
        $c = HtmlPageCrawler::create($html);
        $this->assertEquals(1, count($c->filter('td.c23')));
        $tbd = $c->filter('table > tbody > tr > td')
            ->reduce(
                function($c, $j) {
                    if (($j+1) % 3 == 0) {
                        return true;
                    }
                    return false;
                }
            );
        $this->assertEquals(6, count($tbd));
        $tbd->remove();
        $this->assertEquals(0, count($tbd));
        $this->assertEquals(0, count($c->filter('td.c23')));
    }

    public function testUTF8Characters()
    {
        $text = file_get_contents(__DIR__ . '/utf8.html');
        $c = HtmlPageCrawler::create($text);

        $expected =<<< END
<p style="margin: 0cm 0cm 0pt;"><span>Die Burse&nbsp;wurde unmittelbar (1478 bis 1482) nach der Universit&auml;tsgr&uuml;ndung als Studentenwohnhaus und -lehranstalt errichtet. Hier lehrte der Humanist und Reformator Philipp Melanchthon bis zu seiner Berufung nach Wittenberg 1518, an ihn erinnert eine Gedenktafel. 1803 bis 1805 wurde das Geb&auml;ude im Stil des Klassizismus zum ersten T&uuml;binger Klinikum umgebaut. Einer der ersten Patienten war Friedrich H&ouml;lderlin, der nach einer 231 Tage dauernden Behandlung am 3. Mai 1807 als unheilbar entlassen wurde.</span></p><p style="margin: 0cm 0cm 0pt;"><span>Einst Badeanstalt vor der Stadtmauer. Wer durch das kleine Stadttor geht, hat &ndash; r&uuml;ckw&auml;rts gewandt &ndash; einen guten Blick auf die Stadtbefestigung mit "Pechnasen" und Spuren des alten Wehrgangs.</span></p>
END;

        $this->assertEquals($expected, $c->filter('p')->saveHTML());
    }

}
