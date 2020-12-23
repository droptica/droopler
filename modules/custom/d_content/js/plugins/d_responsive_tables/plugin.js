CKEDITOR.plugins.add( 'd_responsive_tables', {
  requires: 'table',
  init: function (editor) {
    editor.on('insertElement', function (event) {
      if (event.data.getName() === 'table') {
        let div = new CKEDITOR.dom.element('div').addClass('table-responsive');
        div.append(event.data);
        event.data = div;
      }
    }, null, null, 1);
  }
});
