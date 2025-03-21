// Moved from old site.

;(function ($, Drupal) {

  var read_data_from_table = function () {

    var sel = {
      table: '#block-views-block-publications-per-publisher-sorted-by-publisher',
      row: 'tbody tr',
      publisher: '.views-field-title-1',
      year: '.views-field-field-annual-information',
      type: '.views-field-field-soort-uitgever'
    }

    var data = {uitgevers: {}}

    // read data
    var rows = $(sel.table + ' ' + sel.row)

    if (!rows.length) {
      return
    }
    rows.each(function () {
      var t = $(this)
      var publisher = $.trim(t.find(sel.publisher).text())
      var type = $.trim(t.find(sel.type).text()).toLowerCase()

      var yearContainer = t.find(sel.year);
      var counts = {};

      yearContainer.find('ul li').each(function () {
        var year = $(this).find('.year').text().trim();
        var data = parseInt($(this).find('.year_data').text().trim(), 10);
        if (!isNaN(data)) {
          if (!counts[year]) {
            counts[year] = 0;
          }
          counts[year] += data;
        }
      });

      data.uitgevers[publisher] = {uitgever: publisher, soort: type, publicaties: counts}
    })

    // Organize data by year and type for other uses
    data.jaren = {}
    data.soorten = {}
    for (var name in data.uitgevers) {
      var publisher = data.uitgevers[name]
      for (var year in publisher.publicaties) {
        var count = publisher.publicaties[year]
        var type = publisher.soort

        // Organize by year->publisher
        if (!data.jaren[year]) {
          data.jaren[year] = {uitgevers: {}}
        }
        data.jaren[year].uitgevers[name] = publisher

        // Organize by type->year->publisher
        if (!data.soorten[type]) {
          data.soorten[type] = {jaren: {}}
        }
        if (!data.soorten[type].jaren[year]) {
          data.soorten[type].jaren[year] = {uitgevers: {}}
        }
        data.soorten[type].jaren[year].uitgevers[name] = publisher
      }
    }

    return data
  }

  var ballon = (function () {
    var ballon = false

    var toon = function (e) {
      if (!ballon) {
        ballon = $('<div class="svg-ballon">').hide().appendTo($('body'))
      }

      var x, y
      if (e.doel && e.doel.target) {
        x = e.doel.pageX
        y = e.doel.pageY - 24
        ballon
          .text(e.tekst)
          .css({position: 'absolute', left: x + 'px', top: y + 'px'})
          .show()
      }
    } //
    var verberg = function (e) {
      if (ballon) {
        ballon.hide()
      }
    } //

    return {toon: toon, verberg: verberg}
  })();

  var svg = (function () {
    var maak = function (tag, attr, ouder) {
      var is_nieuw = typeof (tag) != 'object'
      var svg_ns = 'http://www.w3.org/2000/svg'
      var e = is_nieuw ? document.createElementNS(svg_ns, tag) : tag
      if (attr) {
        for (var v in attr) {
          if (v == 'tekst') {
            e.textContent = attr[v];
            continue
          }
          e.setAttributeNS(null, v, attr[v])
        }
      }
      if (is_nieuw && ouder) {
        ouder.appendChild(e)
      }
      return e
    } // /maak

    var klasse = (function () {
      var lees = function (e) {
        return e.getAttribute('class')
      } //
      var schrijf = function (e, klasse) {
        e.setAttribute('class', klasse)
      } //

      var heeft = function (e, klasse) {
        return (new RegExp('\\b' + klasse + '\\b')).test(lees(e))
      } //

      var erbij = function (e, klasse) {
        if (!heeft(e, klasse)) {
          schrijf(e, lees(e) + ' ' + klasse)
        }
      }//
      var eraf = function (e, klasse) {
        schrijf(e, lees(e).replace(new RegExp('\\b' + klasse + '\\b'), ' '))
      }

      var wissel = function (e, klasse) {
        heeft(e, klasse) ? eraf(e, klasse) : erbij(e, klasse)
      } //

      return {erbij: erbij, eraf: eraf, wissel: wissel, heeft: heeft}
    })();


    return {
      maak: maak,
      wijzig: maak,
      klasse: klasse
    }
  })();

  var draw_line_graph = function (vat, data, breedte, hoogte) {
    vat = (typeof (vat) == 'string') ? $(vat) : vat
    var buiten = {marge: {boven: 0, rechts: 10, onder: 20, links: 50}}
    var binnen = {marge: {boven: 10, rechts: 10, onder: 10, links: 10}}

    var sx = 0
    var sy = 0

    var gdata = {}

    // configuratie
    gdata.maten = {
      breedte: breedte,
      hoogte: hoogte,
      marges: {binnen: binnen.marge, buiten: buiten.marge}
    }
    gdata.assen = {x: {}, y: {min: 0}}

    // vul data (dataspecifieke conversie)
    gdata.data = {}
    for (var unaam in data.uitgevers) {
      var groep = {label: unaam, punten: []}
      var uitgever = data.uitgevers[unaam]
      for (var jaar in uitgever.publicaties) {
        var aantal = uitgever.publicaties[jaar]
        groep.punten.push({x: jaar, y: aantal})
      }
      gdata.data[unaam] = groep
    }

    // bepaal grenzen van assen
    for (var as in gdata.assen) {
      var min = Number.POSITIVE_INFINITY
      var max = Number.NEGATIVE_INFINITY
      for (var i in gdata.data) {
        var set = gdata.data[i]
        for (var j = 0; j < set.punten.length; j++) {
          var p = set.punten[j]
          min = Math.min(min, p[as])
          max = Math.max(max, p[as])
        }
      }
      if (!('min' in gdata.assen[as])) {
        gdata.assen[as].min = min
      }
      if (!('max' in gdata.assen[as])) {
        gdata.assen[as].max = max
      }
    }

    // posities
    gdata.assen.x.x0 = gdata.maten.marges.buiten.links
    gdata.assen.x.x1 = gdata.maten.breedte - gdata.maten.marges.buiten.rechts
    gdata.assen.x.y0 = gdata.maten.hoogte - gdata.maten.marges.buiten.onder
    gdata.assen.x.y1 = gdata.assen.x.y0

    gdata.assen.y.x0 = gdata.assen.x.x0
    gdata.assen.y.x1 = gdata.assen.y.x0
    gdata.assen.y.y0 = gdata.maten.marges.buiten.boven
    gdata.assen.y.y1 = gdata.assen.x.y1

    gdata.vlak = {
      x0: gdata.assen.x.x0 + gdata.maten.marges.binnen.links,
      x1: gdata.assen.x.x1 - gdata.maten.marges.binnen.rechts,
      y0: gdata.assen.y.y0 + gdata.maten.marges.binnen.boven,
      y1: gdata.assen.y.y1 - gdata.maten.marges.binnen.onder
    }

    gdata.vlak.breedte = gdata.vlak.x1 - gdata.vlak.x0
    gdata.vlak.hoogte = gdata.vlak.y1 - gdata.vlak.y0
    gdata.assen.x.schaal = gdata.vlak.breedte / (gdata.assen.x.max - gdata.assen.x.min)
    gdata.assen.y.schaal = gdata.vlak.hoogte / (gdata.assen.y.max - gdata.assen.y.min)

    var canvas = svg.maak('svg', {
      viewBox: '0 0 ' + gdata.maten.breedte + ' ' + gdata.maten.hoogte,
      style: 'max-height: 90vh',
      class: 'lijngrafiek'
    }, vat.get(0))
    // var canvas=svg.maak('svg',{style:'max-height: 90vh', class:'lijngrafiek'},vat.get(0))

    // x-as
    var svg_x_as = svg.maak('g', {'class': 'x-as'}, canvas)

    var x0 = gdata.assen.x.x0
    var x1 = gdata.assen.x.x1
    var xy = gdata.assen.x.y0
    svg.maak('line', {x1: x0, y1: xy, x2: x1, y2: xy, stroke: '#000'}, svg_x_as)

    var x0 = gdata.vlak.x0
    var xm = gdata.assen.x.min
    var xs = gdata.assen.x.schaal
    for (var i = xm; i < gdata.assen.x.max + 1; i++) {
      var dx = x0 + (i - xm) * xs
      svg.maak('line', {x1: dx, y1: xy, x2: dx, y2: xy + 5, stroke: '#000'}, svg_x_as)
      svg.maak('text', {x: dx, y: xy + 20, 'text-anchor': 'middle', tekst: i}, svg_x_as)
    }

    // y-as
    var svg_y_as = svg.maak('g', {'class': 'y-as'}, canvas)

    var y0 = gdata.assen.y.y0
    var y1 = gdata.assen.y.y1
    var yx = gdata.assen.y.x0
    svg.maak('line', {x1: yx, y1: y0, x2: yx, y2: y1, stroke: '#000'}, svg_y_as)

    var y1 = gdata.vlak.y1
    var ys = gdata.assen.y.schaal
    var dmin = gdata.assen.y.min
    var dmax = gdata.assen.y.max
    var dstap = Math.pow(10, Math.floor(Math.log10(dmax - dmin))) / 2
    for (var i = dmin; i < dmax; i += dstap) {
      var dy = y1 - (i - dmin) * ys
      svg.maak('line', {x1: yx - 5, y1: dy, x2: yx, y2: dy, stroke: '#000'}, svg_y_as)
      svg.maak('text', {x: yx - 10, y: dy, 'text-anchor': 'end', 'dominant-baseline': 'central', tekst: i}, svg_y_as)
    }

    // lijnen
    var x0 = gdata.vlak.x0
    var xm = gdata.assen.x.min
    var xs = gdata.assen.x.schaal

    var y1 = gdata.vlak.y1
    var ym = gdata.assen.y.min
    var ys = gdata.assen.y.schaal

    for (var s in gdata.data) {
      var set = gdata.data[s]
      var groep = svg.maak('g', {'class': 'lijn', 'data-label': set.label}, canvas)
      var lijn = svg.maak('polyline', {stroke: '#f80', fill: 'none'}, groep)
      set.lijn = groep
      var punten = []
      for (var p in set.punten) {
        var p = set.punten[p]

        var dx = x0 + (p.x - xm) * xs
        var dy = y1 - (p.y - ym) * ys
        var punt = svg.maak('circle', {cx: dx, cy: dy, r: 3, fill: '#F00', 'data-x': p.x, 'data-y': p.y}, groep)

        ;(function (punt, set, p) {
          $(punt).hover(
            function (e) {
              ballon.toon({
                doel: e,
                tekst: '' + set.label + ' / ' + p.x + ' / ' + p.y + ''
              })
            },
            function () {
              ballon.verberg(punt)
            }
          )
        })(punt, set, p);

        punten.push([dx, dy].join(','))

      }

      svg.wijzig(lijn, {points: punten.join(' ')})

    }

    // filters
    var form = $('<div class="grafiekfilters dropdown">')
    var droopdown_toogle = $('<button class="btn dropdown-toggle" type="button" id="dropdownPublishers" data-bs-toggle="dropdown" aria-expanded="false">' + Drupal.t('Publishers') +'</button>')
    droopdown_toogle.appendTo(form);
    var dropdown_menu = $('<ul class="dropdown-menu" aria-labelledby="dropdownPublishers">')
    var i = 1;
    for (var s in gdata.data) {
      (function () {
        var set = gdata.data[s]
        var lijn = $(set.lijn)
        var item = $('<div class="item">')
        var label = $('<label for="checkbox' + i + '">').text(set.label)
        var filter = $('<input type="checkbox" checked id="checkbox' + i + '">')
        i++
        filter.change(function () {
          var t = $(this)
          var is_zichtbaar = t.is(':checked')

          lijn.css('visibility', is_zichtbaar ? 'visible' : 'hidden').toggleClass('uit')
        })

        label.hover(
          function () {
            item.addClass('actief')
            svg.klasse.erbij(lijn.get(0), 'actief')
          },
          function () {
            item.removeClass('actief')
            svg.klasse.eraf(lijn.get(0), 'actief')
          })

        lijn.hover(
          function () {
            item.addClass('actief')
            svg.klasse.erbij(this, 'actief')
          },
          function () {
            item.removeClass('actief')
            svg.klasse.eraf(this, 'actief')
          })

        filter.prependTo(item)
        label.appendTo(item)
        item.appendTo(dropdown_menu)
      })()
    }

    console.log(gdata.data)

    dropdown_menu.appendTo(form)

    form.appendTo(vat)
  } //teken_lijn_grafiek

  $.fn.rowMerger = function () {
    const tableBlock = $(this);

    let uniqueNodes = new Set();

    $('table tbody tr', tableBlock).each(function () {
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

  function graphWidth() {
    const width = window.innerWidth;
    if (width < 768) return 320;
    if (width < 992) return 768;
    if (width < 1200) return 1024;
    return 1200;
  }

  Drupal.behaviors.oa_graphs = {
    attach: function (c, s) {
      $('#block-views-block-publications-per-publisher-sorted-by-publisher').rowMerger();

      var data = read_data_from_table()

      if (data) {
        $('#block-views-block-publications-per-publisher-sorted-by-publisher').after($("<div>").addClass('line_graph_wrapper').addClass('publications-per-publisher-sorted-by-publisher'));
        draw_line_graph('.line_graph_wrapper', data, graphWidth(), 490);
        window.addEventListener('resize', () => {
          $('.line_graph_wrapper').empty();
          draw_line_graph('.line_graph_wrapper', data, graphWidth(), 490);
        });
      }

      const dropdownButton = $('#dropdownPublishers');
      const checkboxes = $('.grafiekfilters .item input[type="checkbox"]');

      function updateButtonLabel() {
        const selectedCount = checkboxes.filter(':checked').length;
        dropdownButton.text(`${Drupal.t('Publishers')} (${selectedCount})`);
      }

      checkboxes.on('change', function() {
        updateButtonLabel();
      });

      updateButtonLabel();
    }
  }

})(jQuery, Drupal);
