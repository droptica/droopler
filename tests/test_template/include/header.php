<?php

/**
 * Return HTML code to display header on report page
 *
 * @param $report_type - typ of report
 */
function display_visual_header($report_type = 'visual')
{
    date_default_timezone_set('Europe/Warsaw');
    $date = date('d/m/Y h:i:s a', time());
    if ($report_type == 'visual') {
        $test_page = str_replace("", "", getenv('DEV_URI'));
    } elseif ($report_type == 'compare') {
        $test_page = str_replace("", "", getenv('DEV_URI'));
    }

    $prod_page = str_replace("", "", getenv('PROD_URI'));
    $test_compare = getenv('NO_COMPARE');
    $prod_compare = getenv('NO_COMPARE_PROD');
    $logo = base64_encode(file_get_contents(__DIR__ . '/img/logo-droptica.svg'));
    print("  <div class=\"header\">
    <div class=\"header-item header-left\">
      <img class=\"logo\" src='data:image/svg+xml;base64," . $logo . "'/>
      <h1>VisualCeption Report</h1>
    </div>
    <div class=\"header-item header-right\">
      <div class=\"title-param\">Test parameters</div>
      <div class=\"test-param\">
        <div class=\"label\">Date of test execution:</div>
        <div class=\"value\">" . $date . "</div>
      </div>
      <div class=\"test-param\">
        <div class=\"label\">Address of the test page</div>
        <div class=\"value\">" . $test_page . "</div>
      </div>");
    if ($report_type == 'visual') {
        print("<div class=\"test-param\">
        <div class=\"label\">Test user login</div>
        <div class=\"value\">admin</div>
      </div>
      <div class=\"test-param\">
        <div class=\"label\">Test user password</div>
        <div class=\"value\">123</div>
      </div>");
    } elseif ($report_type == 'compare') {
        print("<div class=\"test-param\">
        <div class=\"label\">Production page:</div>
        <div class=\"value\">" . $prod_page . "</div>
      </div>
      <div class=\"test-param\">
        <div class=\"label\">Elements not compared on the production page:</div>
        <div class=\"value\">" . $test_compare . "</div>
      </div>
      <div class=\"test-param\">
        <div class=\"label\">Elements not compared on the test page:</div>
        <div class=\"value\">" . $prod_compare . "</div>
      </div>");
    }
    print("</div>
  </div>");
}
