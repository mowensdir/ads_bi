(function ($, window, document) {
  $(function () {
    var inactiveRendered = false,
        inactiveTable    = null;

    var activeTable = $("#active-table").DataTable({
      "ajax": "/data/drivers/customer-reports/activedrivers",
      "scrollY": 600,
      "deferRender": true,
      "scroller": true,
      "columns": [
        {"data": "a"},
        {"data": "b"},
        {"data": "c", "searchable": false},
        {"data": "d", "searchable": false},
        {"data": "e", "searchable": false},
        {"data": "f", "searchable": false},
        {"data": "g", "visible": false, "searchable": false},
        {"data": "h", "visible": false, "searchable": false},
      ],
      "order": [[7, "desc"], [5, "desc"], [4, "desc"]]
    });

    $("a[data-toggle='tab']").on("shown.bs.tab", function(e) {
      if ('#inactive' === $(e.target).attr('href') && !inactiveRendered) {
        inactiveTable = $("#inactive-table").DataTable({
          "ajax": "/data/drivers/customer-reports/inactivedrivers",
          "scrollY": 600,
          "deferRender": true,
          "scroller": true,
          "columns": [
            {"data": "a"},
            {"data": "b"},
            {"data": "c", "searchable": false},
            {"data": "d", "searchable": false},
            {"data": "e", "searchable": false},
            {"data": "f", "searchable": false},
            {"data": "g", "visible": false, "searchable": false},
            {"data": "h", "visible": false, "searchable": false},
          ],
          "order": [[7, "desc"], [5, "desc"], [4, "desc"]]
        });
        inactiveRendered = true;
      }
    });

    $("#active-table tbody").on("click", "tr", function() {
      var data = activeTable.row(this).data(),
          did  = data.g;
      window.open("/app/drivers/customer-reports/" + did, "_blank");
    });

    $("#driverSearchSubmit").click(function(e) {
      e.preventDefault();
      $("#driverSearchForm").submit();
    });

    if (typeof resultsData !== 'undefined') {
      var resultsTable = $("#searchresults-table").DataTable({
        "data": resultsData,
        "scrollY": 600,
        "deferRender": true,
        "scroller": true,
        "columns": [
          {"data": "DriverID"},
          {"data": "CustomerName"},
          {"data": "DLN"},
          {"data": "State"},
          {"data": "Active"},
          {"data": "Dealer"},
          {"data": "InstallDate"},
          {"data": "LastDownload"},
        ],
        "order": [[4, "desc"], [2, "asc"]]
      });

      $("#searchresults-table tbody").on("click", "tr", function() {
      var data = resultsTable.row(this).data(),
          did  = data.DriverID;
      window.location.href = "/app/drivers/customer-reports/" + did;
    });
    }

    if (typeof downloadData !== 'undefined') {
      var downloadsTable = $("#downloads-table").DataTable({
        "data": downloadData,
        "columns": [
          {"data": "Download"},
          {"data": "Dealer"},
          {"data": "Equipment"},
          {
            "data": "driverlink",
            "render": function(data, type, row, meta) {
              if (type === 'display') {
                data = '<a href="' + data + '">Driver Report</a>';
              }
              return data;
            }
          },
          {
            "data": "summarylink",
            "render": function(data, type, row, meta) {
              if (type === 'display') {
                data = '<a href="' + data + '">Summary Report</a>';
              }
              return data;
            }
          },
        ],
        "order": [[0, "desc"]],
        "paging": false,
        "searching": false,
      });
    }
  });
}(jQuery, window, document));