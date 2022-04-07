(function ($, window, document) {
  $(function () {
    if ($('#datasetAddDriverReviewGoBack').length) {
      $('#datasetAddDriverReviewGoBack').click(function() {
        window.history.back();
      });
    }

    if ($('#datasetAddDriverRun input[name="json"]').length && typeof postData !== 'undefined') {
      $('#datasetAddDriverRun input[name="json"]').val(JSON.stringify(postData));
    }

    if ($('#datasetAddDriverRun').length && $('#datasetAddDriverDoInsert').length) {
      $('#datasetAddDriverDoInsert').click(function() {
        $('#datasetAddDriverRun').submit();
      });
    }

    var reviewTable = $("#batchreview-table").DataTable({
      "data": reviewData,
      "scrollY": 450,
      "scroller": true,
      "searching": false,
      "columns": [
        {"data": "DriverID"},
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