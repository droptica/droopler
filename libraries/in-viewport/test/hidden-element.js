var supportsMutationObserver = typeof global.MutationObserver === 'function';
if (supportsMutationObserver) {
  describe('asking if a hidden div is in the viewport', function() {
    require('./fixtures/bootstrap.js');
    beforeEach(h.clean);
    afterEach(h.clean);

    var visible = false;
    var test;

    beforeEach(function() {
      test = h.createTest({
        style: {
          width: '500px',
          height: '500px',
          display: 'none'
        }
      });
      h.insertTest(test);
      inViewport(test, function() {
        visible = true;
      });
    });

    // scrolling down and up, should not call the callback: element is not visible
    beforeEach(h.scroller(0, 100));
    beforeEach(h.scroller(0, 0));

    it('callback not called', function() {
      assert(visible === false);
    });

    describe('when element becomes visible', function(done) {
      beforeEach(function(done) {
        test.style.display = 'block';
        setTimeout(done, 40);
      });

      it('callback called', function() {
        assert(visible === true);
      });
    });
  });
}
