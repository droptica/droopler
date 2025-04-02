<?php

/**
 * Get and return list of failed tests
 *
 * @param $environment
 * @return array|string
 */
function get_failed_test($environment)
{
    try {
        $newFailedTest = array();
        if ($handle = opendir('../../tests/tests/_output/debug/')) {
            while (false !== ($entry = readdir($handle))) {
                if (
                    $entry != "." &&
                    $entry != ".." &&
                    strpos($entry, '.png') !== false &&
                    strpos($entry, $environment) !== false
                ) {
                    array_push($newFailedTest, substr($entry, 7));
                    sort($newFailedTest);
                }
            }
            closedir($handle);
            return $newFailedTest;
        }
    } catch (Exception $e) {
        return 'No dir ../../tests/tests/_output/debug/ Caught exception:  \n';
    }
}

/**
 * Get and return list of passed tests
 *
 * @param $environment
 * @return array|string
 */
function get_passed_test($environment)
{
    try {
        $newFailedTest = get_failed_test($environment);
        $newPassedTest = array();
        $allTests = array();
        if ($handle = opendir('../../tests/tests/_output/debug/visual/')) {
            while (false !== ($entry = readdir($handle))) {
                if (
                    $entry != "." &&
                    $entry != ".." &&
                    strpos($entry, '.png') !== false &&
                    strpos($entry, $environment) !== false
                ) {
                    array_push($allTests, $entry);
                    $newPassedTest = array_diff($allTests, $newFailedTest);
                    sort($newPassedTest);
                }
            }

            closedir($handle);
            return $newPassedTest;
        }
    } catch (Exception $e) {
        return 'No dir ../../tests/tests/_output/debug/visual/ Caught exception: \n';
    }
}

/**
 * Return HTML code to display failed tests on report page
 *
 * @param $environment
 */
function display_failed_test($environment)
{
    $newFailedTest = get_failed_test($environment);
    $icon = base64_encode(file_get_contents(__DIR__ . '/img/link_icon.svg'));
    $linkIcon = base64_encode(file_get_contents(__DIR__ . '/img/link_icon.svg'));
    foreach ($newFailedTest as $name) {
        $DeviationImagePath = './debug/compare' . $name;
        $ExpectedImagePath = './../../../_data/VisualCeption/' . $name;
        $CurrentImagePath = './debug/visual/' . $name;
        $shortName1 = substr($name, strpos($name, '.') + 1);
        $shortName2 = substr($shortName1, strpos($shortName1, '.') + 1);
        $shortName3 = substr($shortName2, strpos($shortName2, '.') + 1);
        $shortName4 = substr($shortName3, 0, strpos($shortName3, '.'));
        print("    <div class=\"section-compare dropdown-toggle\">

		<h2 class=\"dropbtn\">" . $shortName4 . "</h2>

		<div class=\"dropdown-content\">
			<div class=\"deviationimage item-compare visual\">
				<h3>Deviation Image <img src='data:image/svg+xml;base64," . $icon . "'/></h3>
				<img class=\"lazy\" data-src='" . $DeviationImagePath . "' />
			</div>

			<div class=\"expectedimage item-compare\">
				<h3> <img class=\"icon\" src='data:image/svg+xml;base64," . $linkIcon . "'/> Reference Image</h3>
				<img	class=\"lazy\" data-src='" . $ExpectedImagePath . "' />
			</div>

			<div class=\"currentimage item-compare\">
				<h3> <img class=\"icon\" src='data:image/svg+xml;base64," . $linkIcon . "'/>Image from test</h3>
				<img	class=\"lazy\" data-src='" . $CurrentImagePath . "' />
			</div>
		</div>

	</div>");
    }
}

/**
 * Return HTML code to display passed tests on report page
 *
 * @param $environment
 */
function display_passed_test($environment)
{
    $newPassedTest = get_passed_test($environment);
    $linkIcon = base64_encode(file_get_contents(__DIR__ . '/img/link_icon.svg'));
    foreach ($newPassedTest as $name) {
        $ExpectedImagePath = './../../../_data/VisualCeption/' . $name;
        $CurrentImagePath = './debug/visual/' . $name;
        $shortName1 = substr($name, strpos($name, '.') + 1);
        $shortName2 = substr($shortName1, strpos($shortName1, '.') + 1);
        $shortName3 = substr($shortName2, strpos($shortName2, '.') + 1);
        $shortName4 = substr($shortName3, 0, strpos($shortName3, '.'));
        print("<div class=\"section-compare dropdown-toggle\">

		<h2 class=\"dropbtn passed\">" . $shortName4 . "</h2>

		<div class=\"dropdown-content\">
			<div class=\"expectedimage item-compare item-passed\">
				<h3><img class=\"icon\" src='data:image/svg+xml;base64," . $linkIcon . "'/> Reference Image</h3>
				<img	class=\"lazy\" data-src='" . $ExpectedImagePath . "' />
			</div>

			<div class=\"currentimage item-compare item-passed\">
				<h3><img class=\"icon\" src='data:image/svg+xml;base64," . $linkIcon . "'/> Image from test</h3>
				<img	class=\"lazy\" data-src='" . $CurrentImagePath . "' />
			</div>
		</div>

	</div>");
    }
}
