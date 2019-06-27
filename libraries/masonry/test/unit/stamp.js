QUnit.test( 'stamp top left', function( assert ) {

  var msnry = new Masonry( '#stamp-top-left', {
    itemSelector: '.item',
    stamp: '.stamp'
  });

  checkItemPositions( msnry, assert, {
    0: { left: 0, top: 20 },
    1: { left: 135, top: 20 },
    2: { left: 45, top: 40 },
    3: { left: 90, top: 40 }
  });

});

QUnit.test( 'stamp columnWidth multiple', function( assert ) {

  var msnry = new Masonry( '#stamp-column-width-multiple', {
    itemSelector: '.item',
    stamp: '.stamp'
  });

  checkItemPositions( msnry, assert, {
    0: { left: 0, top: 0 },
    1: { left: 135, top: 0 },
    2: { left: 0, top: 30 },
    3: { left: 45, top: 30 }
  });

});

QUnit.test( 'stamp bottom right', function( assert ) {

  var msnry = new Masonry( '#stamp-bottom-right', {
    itemSelector: '.item',
    stamp: '.stamp',
    isOriginLeft: false,
    isOriginTop: false
  });

  checkItemPositions( msnry, assert, {
    0: { right: 0, bottom: 20 },
    1: { right: 135, bottom: 20 },
    2: { right: 45, bottom: 40 },
    3: { right: 90, bottom: 40 }
  });

});
