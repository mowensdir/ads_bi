(function ($, window, document) {
  $(function () {
    if ($('#batchShipDateReviewGoBack').length) {
      $('#batchShipDateReviewGoBack').click(function() {
        window.history.back();
      });
    }

    if ($('#batchShipDateRun input[name="json"]').length && typeof postData !== 'undefined') {
      $('#batchShipDateRun input[name="json"]').val(JSON.stringify(postData));
    }

    if ($('#batchShipDateRun').length && $('#batchShipDateDoUpdate').length) {
      $('#batchShipDateDoUpdate').click(function() {
        $('#batchShipDateRun').submit();
      });
    }

    var reviewTable = $("#batchreview-table").DataTable({
      "data": reviewData,
      "scrollY": 450,
      "scroller": true,
      "searching": false,
      "columns": [
        {"data": "DriverID"},
        {"data": "ShipDate"},
        {"data": "Message"}
      ],
      "createdRow": function(row, data, dataIndex, cells) {
        if (data.status.length) {
          $(row).addClass('table-' + data.status);
        }
      }
    });
  });
}(jQuery, window, document));