QUnit.test( 'element sizing', function( assert ) {
  var container = document.querySelector('#element-sizing');
  var msnry = new Masonry( container, {
    columnWidth: '.grid-sizer',
    itemSelector: '.item',
    transitionDuration: 0
  });

  var lastItem = msnry.items[3];

  var containerWidth = 170;
  while ( containerWidth < 190 ) {
    container.style.width = containerWidth + 'px';
    msnry.layout();
    assert.equal( lastItem.position.y, 0,'4th item on top row, container width = ' + containerWidth );
    containerWidth++;
  }

});
