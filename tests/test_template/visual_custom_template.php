<?php include_once "include/feed.php" ?>
<?php include_once "include/header.php" ?>
<?php include_once "include/result.php" ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <?php
    $numberOfBlogPost = get_number_of_blog_post();
    $blog_post = get_blog_post($numberOfBlogPost);
    $image = $blog_post["image"];
    ?>
    <title>VisualCeption Report</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <!-- cdnjs -->
    <script type="text/javascript"
            src="https://cdnjs.cloudflare.com/ajax/libs/jquery.lazy/1.7.9/jquery.lazy.min.js"></script>
    <script type="text/javascript"
            src="https://cdnjs.cloudflare.com/ajax/libs/jquery.lazy/1.7.9/jquery.lazy.plugins.min.js"></script>

    <style>
        <?php include_once "CSS/style.css" ?>
    </style>

</head>
<body>
<body>
<?php display_blog_post($numberOfBlogPost); ?>
<?php display_visual_header('visual'); ?>
<?php
try {
    $newfailedTest = array();
    if ($handle = opendir('../tests/tests/_output/debug/')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && strpos($entry, '.png') !== false) {
                array_push($newfailedTest, substr($entry, 7));
            }
        }

        closedir($handle);
    }
} catch (Exception $e) {
    echo 'No dir ../tests/tests/_output/debug/ Caught exception: ',  $e->getMessage(), "\n";
}
try {
    $newPassedTest = array();
    $allTests = array();
    if ($handle = opendir('../tests/tests/_output/debug/visual/')) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && strpos($entry, '.png') !== false) {
                array_push($allTests, $entry);
                $newPassedTest = array_diff($allTests, $newfailedTest);
            }
        }

        closedir($handle);
    }
} catch (Exception $e) {
    echo 'No dir ../tests/tests/_output/debug/visual/ Caught exception: ',  $e->getMessage(), "\n";
}
foreach ($newfailedTest as $name) : ?>
    <?php

    $DeviationImagePath =  './debug/compare' . $name;
    $ExpectedImagePath = './../../_data/VisualCeption/' . $name;
    $CurrentImagePath = './debug/visual/' . $name;

    ?>

    <div class="section-compare dropdown-toggle">

        <h2 class="dropbtn"><?php echo $name ?></h2>

        <div class="dropdown-content">
            <div class="deviationimage item-compare">
                <h3>Deviation Image</h3>
                <img class="lazy" data-src='<?= $DeviationImagePath ?>' />
            </div>

            <div class="expectedimage item-compare">
                <h3>
                    Expected Image
                </h3>
                <img    class="lazy" data-src='<?= $ExpectedImagePath ?>' />
            </div>

            <div class="currentimage item-compare">
                <h3>
                    Current Image
                </h3>
                <img    class="lazy" data-src='<?= $CurrentImagePath ?>' />
            </div>
        </div>

    </div>

<?php endforeach; ?>

<?php
foreach ($newPassedTest as $name) : ?>
    <?php

    $ExpectedImagePath = './../../_data/VisualCeption/' . $name;
    $CurrentImagePath = './debug/visual/' . $name;

    ?>

    <div class="section-compare dropdown-toggle">

        <h2 class="dropbtn passed"><?php echo $name ?></h2>

        <div class="dropdown-content">
            <div class="expectedimage item-compare item-passed">
                <h3>
                    Expected Image
                </h3>
                <img    class="lazy" data-src='<?= $ExpectedImagePath ?>' />
            </div>

            <div class="currentimage item-compare item-passed">
                <h3>
                    Current Image
                </h3>
                <img    class="lazy" data-src='<?= $CurrentImagePath ?>' />
            </div>
        </div>

    </div>

<?php endforeach; ?>

  <script>
    $(document).ready(function(){
      $(".dropbtn").click(function(){
        //slide up all the link lists
        $(this).next('.dropdown-content').slideToggle();
        $(this).toggleClass('show');
      })
    });
  </script>

  <script>
    $(function() {
      $('.dropbtn').click(function() {
        $(this).next('.dropdown-content').find('.lazy').lazy({
          bind: "event",
          delay: 0
        });
      });
    });
  </script>

</body>
</html>
