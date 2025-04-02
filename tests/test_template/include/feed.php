<?php

/**
 * Get string between two other strings
 *
 * @param $string
 * @param $start
 * @param $end
 * @return false|string
 */
function get_string_between($string, $start, $end)
{
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) {
        return '';
    }
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

/**
 * Get random number for blog post
 *
 * @return int
 */
function get_number_of_blog_post()
{
    return $numberOfBlogPost = rand(0, 9);
}

/**
 * Get blog post from Droptica RSS
 * @param $numberOfBlogPost
 * @return array
 */
function get_blog_post($numberOfBlogPost)
{
    $feeds = simplexml_load_file("https://www.droptica.com/rss.xml");
    $title = $feeds->channel->item[$numberOfBlogPost]->title;
    $link = $feeds->channel->item[$numberOfBlogPost]->link;
    $description = $feeds->channel->item[$numberOfBlogPost]->description;
    $postDate = $feeds->channel->item[$numberOfBlogPost]->pubDate;
    $pubDate = date('D, d M Y', strtotime($postDate));
    $image = get_string_between($description, '<img src="', '" width="');
    $link = str_replace(
        '?utm_source=rss&amp;utm_medium=rss&amp;utm_content=rss?utm_source=rss&utm_medium=rss&utm_content=rss',
        '?utm_source=qa_report&utm_medium=qa_report&utm_content=qa_report',
        $link
    );
    $descriptionShort = get_string_between(
        $description,
        '<div class="clearfix text-formatted field 
field--name-field-d-long-text field--type-text-long field--label-hidden field__item"><p>',
        ' </div> </div>'
    );
    $descriptionShort = strip_tags($descriptionShort, "<a>");
    return $blog_post = array(
        "title" => $title,
        "image" => $image,
        "link" => $link,
        "description" => $descriptionShort,
        "pubDate" => $pubDate);
}

function display_blog_post($numberOfBlogPost)
{
    $blog_post = get_blog_post($numberOfBlogPost);
    $blog_post_description = ' - ' . implode(
        ' ',
        array_slice(
            explode(' ', $blog_post["description"]),
            0,
            80
        )
    )
        . "...";
    print("<div class=\"post\">
      <div class=\"img-responsive \"></div>
          <div class=\"post-content\">
           <a class=\"feed_title\" href=\"" . $blog_post["link"] . "\">" . $blog_post["title"] . "</a>
           " . $blog_post_description . "<a href=\"" . $blog_post["link"] . "\">READ MORE</a>
           </div>
      </div>");
}
