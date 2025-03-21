// Moved from old site.

;(function($, Drupal){
  var createGraphs = function(){

    $.fn.graph_1 = function(position) {
      var theView = $(this)
      var row = '<div class="row"><div class="publisher">*publisher*</div><div class="publications"><div class="bar" style="width:*width*%;"></div><span class="bar-label">*publications*</span></div></div>';
      var selectYear = '';
      var yearTotal = 0;
      var all = [];
      var transition = 200;

      /* Wrapper */
      theView.after('<div id="graph_1" class="publications-per-publisher-sorted-by-year"></div>');

      /* Find the highest */
      $('table td', theView).each(function(){
        if(parseInt($(this).text()) > 0){
          all.push(parseInt($(this).text()) );
        }
      })

      function setGraph(){
        var initialTransition = transition;
        var graph = '';

        $('#graph_1 .graph_inner').addClass('bye')

        if(!$('#graph_1 .graph_inner').length	){
          transition = 10;
        }

        yearTotal = 0;

        var t = setTimeout(function(){
          transition = initialTransition;
          $('#graph_1 .graph_inner').remove()
          let highestValue = 0;
          $('table tr', theView).each(function() {
            let numPublications = 0;
            $('.views-field-field-annual-information ul li', $(this)).each(function () {
              let year = $(this).find('.year').text().trim();
              if (year === currentYear) {
                numPublications = parseInt($(this).find('.year_data').text().trim(), 10);
                if (numPublications > highestValue) {
                  highestValue = numPublications;
                }
              }
            });
          });

          /* Loop through rows */
          $('table tr', theView).each(function(){
            var width = 0;
            let numPublications = 0;

            $('.views-field-field-annual-information ul li', $(this)).each(function() {
              let year = $(this).find('.year').text().trim();
              if (year === currentYear) {
                numPublications = parseInt($(this).find('.year_data').text().trim(), 10);
              }
            });


            let publisherType = this.querySelector('.views-field-field-soort-uitgever').innerText.trim()
            let activeTab = document.querySelector('.quicktabs-tabs .active').innerText.trim()

            if (activeTab == "Full Gold") {
              // The active tab
              if (publisherType == "Full Gold") {
                // all publishers that are gold
                if(numPublications > 0){
                  width = (numPublications / highestValue) * 80
                  yearTotal += numPublications ;

                  var newRow = row
                  newRow = newRow.replace('*publisher*', $('td', this).eq(0).text())
                  newRow = newRow.replace('*publications*', numPublications)
                  newRow = newRow.replace('*width*', width)

                  graph += newRow;
                }
              }
            } else {
              // all publishers that are not gold
              if (publisherType == "Hybrid") {
                if(numPublications > 0){
                  width = (numPublications / highestValue) * 80
                  yearTotal += numPublications ;

                  var newRow = row
                  newRow = newRow.replace('*publisher*', $('td', this).eq(0).text())
                  newRow = newRow.replace('*publications*', numPublications)
                  newRow = newRow.replace('*width*', width)

                  graph += newRow;
                }
              }
            }
          })


          let year = (new Date()).getFullYear()
          let selected = parseInt(document.querySelector('#selectYear').querySelector('option:checked').innerText)
          if (year === selected) {
            $('.total h3').text(yearTotal + '*');
          } else {
            $('.total h3').text(yearTotal);
          }

          if (yearTotal === 0) {
            graph += "<span class='notfound'>" + Drupal.t('No publications found') + "</span>";
          }

          graph = '<div class="graph_inner">'+graph+'</div>';
          $('#selectYear').after(graph);

          setTimeout(function(){
            $('#graph_1 .graph_inner').addClass('hello')
          }, 10)

        }, transition)
      }
      let years = [];

      $('table .year', theView).each(function() {
        let year = $(this).text().trim();
        if (!years.includes(year)) {
          years.push(year);
        }
      });

      years.sort((a, b) => a - b);

      selectYear = '';
      let optionTemplate = '<option value="*value*" *selected*>*label*</option>';

      years.forEach((year, index) => {
        let selected = '';
        if (index === years.length - 1) {
          selected = 'selected';
        }
        let newOption = optionTemplate
          .replace('*value*', year)
          .replace('*label*', year)
          .replace('*selected*', selected);
        selectYear += newOption;
      });

      let currentYear = years[years.length - 1];

      selectYear = '<label></label><select id="selectYear" class="icon-down-open">'+selectYear+"</select>";
      totalLabel = Drupal.t('Total');

      $('#graph_1').append(selectYear)
      $('#graph_1').append('<div class="total_wrapper"><h2>'+totalLabel+': </h2> <div class="total"><h3></h3></div></div>')

      $('#selectYear').on('change',function(){
        currentYear = $(this).val();
        setGraph();
      })

      function createTabs() {
        let tabs = '<ul class="hybrid_gold_tabs quicktabs-tabs quicktabs-style-nostyle"> <li class="active first"> <a class="quicktabs-tab quicktabs-tab-view quicktabs-tab-view-publishers-deals-block quicktabs-loaded jquery-once-3-processed"> ' + Drupal.t('Hybrid') + ' </a> </li> <li> <a class="quicktabs-tab quicktabs-tab-view quicktabs-tab-view-publishers-deals-block jquery-once-3-processed"> ' + Drupal.t('Full Gold') +' </a> </li> </ul>'

        $('#graph_1').before(tabs);
      }

        setGraph();
        createTabs();

        $('.quicktabs-tabs li').click(function (params) {
          $('.quicktabs-tabs li').removeClass('active')
          $(this).addClass('active')
          setGraph();
        })
    };

    $.fn.rowMerger = function() {
      const tableBlock = $(this);

      let uniqueNodes = new Set();

      $('table tbody tr', tableBlock).each(function() {
        let classes = $(this).attr('class').split(' ');

        let nodeId = classes.find(cls => cls.startsWith('node-'));

        if (nodeId) {
          if (uniqueNodes.has(nodeId)) {
            $(this).remove();
          } else {
            uniqueNodes.add(nodeId);
          }
        }
      });

      const table = $('table tbody', tableBlock);
      const rows = $('tr', table);
      const mergedData = {};

      rows.each(function () {
        const row = $(this);
        const categoryClass = row.attr('class').split(' ')[0];

        if (categoryClass) {
          const annualInfo = row.find('.views-field-field-annual-information ul');
          const annualInfoHtml = annualInfo.html() || '';

          if (!mergedData[categoryClass]) {
            mergedData[categoryClass] = {
              title: row.find('.views-field-title-1').html(),
              annualInformation: [],
              publisherType: row.find('.views-field-field-soort-uitgever').text().trim(),
            };
          }

          if (annualInfoHtml) {
            const listItems = annualInfo.find('li').toArray();
            listItems.forEach(item => {
              mergedData[categoryClass].annualInformation.push($(item).prop('outerHTML'));
            });
          }
        }
      });

      const newTable = $('<tbody></tbody>');

      Object.values(mergedData).forEach(data => {
        const row = $('<tr></tr>');

        row.append(`<td class="views-field-title-1">${data.title}</td>`);

        const uniqueAnnualInfo = [...new Set(data.annualInformation)].join('');
        row.append(
          `<td class="views-field-field-annual-information"><ul>${uniqueAnnualInfo}</ul></td>`
        );

        row.append(
          `<td class="views-field-field-soort-uitgever">${data.publisherType}</td>`
        );

        newTable.append(row);
      });

      table.replaceWith(newTable);
    };

    $('#block-views-block-publications-per-publisher-sorted-by-year').rowMerger();
    $('#block-views-block-publications-per-publisher-sorted-by-year').graph_1('.field-name-field-title2');
  }

  Drupal.behaviors.openaccessGraphs={ attach:function(context, settings){
    createGraphs()
  }}

})(jQuery, Drupal);
