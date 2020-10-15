(function ($, window, document) {
  function format(d) {
    return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
      '<tr>' +
        '<td>License Number:</td>' +
        '<td>'+d['drDLN']+'</td>' +
      '</tr>' +
      '<tr>' +
        '<td>Email:</td>' +
        '<td>'+d['drEmail']+'</td>' +
      '</tr>' +
      '<tr>' +
        '<td>Phone:</td>' +
        '<td>'+d['drPhone']+'</td>' +
      '</tr>' +
      '<tr>' +
        '<td>Install Date:</td>' +
        '<td>'+d['install']+'</td>' +
      '</tr>' +
      '<tr>' +
        '<td>HH Assigned:</td>' +
        '<td>'+d['hhsn']+'</td>' +
      '</tr>' +
      '<tr>' +
        '<td>VM Assigned:</td>' +
        '<td>'+d['vmsn']+'</td>' +
      '</tr>' +
    '</table>';
  }
  $(function () {
    var unresolvedRendered = false,
        resolvedRendered   = false,
        removedRendered    = false,
        unresolvedTable    = null,
        resolvedTable      = null,
        removedTable       = null;

    var dealerbdTable = $("#dealerbd-table").DataTable({
      "data": dealerbdData,
      "scrollY": 600,
      "scroller": true,
      "columns": [
        {"data": "dealer"},
        {"data": "drivers"},
        {"data": "unresolved"},
        {"data": "shipped"},
        {"data": "resolved"},
        {"data": "removed"},
        {"data": "progress"},
      ],
      "order": [[0, "asc"]],
      "dom": "Bfrti",
      "buttons": [
        {
          "extend": "excel",
          "className": "btn-warning",
          "text": "<i class='far fa-file-excel mr-2'></i> Excel",
          "filename": slug+'Breakdown',
          "messageTop": null,
          "title": null,
        }
      ],
      "language": {
        "emptyTable": "No data to display"
      }
    });

    $("a[data-toggle='tab']").on("shown.bs.tab", function(e) {
      if ('#unresolved' === $(e.target).attr('href') && !unresolvedRendered) {
        unresolvedTable = $("#unresolved-table").DataTable({
          "data": unresolvedData,
          "scrollY": 600,
          "scroller": true,
          "columns": [
            { // 0
              "className": "details-control",
              "orderable": false,
              "data": null,
              "defaultContent": ''
            },
            { // 1
              "data": "drName",
              "className": "cell-driver-name"
            },
            { // 2
              "data": "statusHH",
              "className": "cell-hh-status"
            },
            { // 3
              "data": "shipped",
              "className": "cell-shipped"
            },
            { // 4
              "data": "lastService",
              "className": "cell-last-service"
            },
            { // 5
              "data": "daysSince",
              "className": "cell-days-since"
            },
            { // 6
              "data": "lastHH",
              "className": "cell-last-hh"
            },
            { // 7
              "data": "nextService",
              "className": "cell-next-service"
            },
            { // 8
              "data": "deName",
              "className": "cell-dealer-name"
            },
            { // 9
              "data": "id",
              "title": "Driver ID",
              "visible": false,
              "searchable": false
            },
            { // 10
              "data": "drDLN",
              "title": "Driver License Number",
              "visible": false
            },
            { // 11
              "data": "drPhone",
              "title": "Driver Phone",
              "visible": false,
              "searchable": false
            },
            { // 12
              "data": "drEmail",
              "title": "Driver Email",
              "visible": false,
              "searchable": false
            },
            { // 13
              "data": "install",
              "title": "Install Date",
              "visible": false,
              "searchable": false
            },
            { // 14
              "data": "hhsn",
              "title": "HH Assigned",
              "visible": false
            },
            { // 15
              "data": "vmsn",
              "title": "VM Assigned",
              "visible": false
            },
          ],
          "order": [[5, "asc"]],
          "dom": "Bfrti",
          "buttons": [
            {
              "extend": "excel",
              "className": "btn-warning",
              "text": "<i class='far fa-file-excel mr-2'></i> Excel",
              "filename": slug+'Unresolved',
              "messageTop": null,
              "title": null,
              "exportOptions": {
                "columns": [9, 1, 10, 2, 11, 12, 8, 13, 14, 15, 3, 4, 6, 7]
              }
            }
          ],
          "language": {
            "emptyTable": "No data to display"
          },
          "createdRow": function(row, data, dataIndex, cells) {
            var days = parseInt(data.daysSince);
            if (60 <= days && days < 100) {
              $(row).find(".cell-days-since").first().addClass("table-warning");
            } else if (100 < days) {
              $(row).find(".cell-days-since").first().addClass("table-danger");
            }
          }
        });

        $("#unresolved-table tbody").on("click", "td.details-control", function() {
          var tr = $(this).closest("tr");
          var row = unresolvedTable.row(tr);

          if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass("shown");
          } else {
            // Open this row
            row.child(format(row.data())).show();
            tr.addClass("shown");
          }
        });

        unresolvedRendered = true;
      } else if ('#resolved' === $(e.target).attr('href') && !resolvedRendered) {
        resolvedTable = $("#resolved-table").DataTable({
          "data": resolvedData,
          "scrollY": 600,
          "scroller": true,
          "columns": [
            {"data": "DriverName", "className": "cell-driver-name"},
            {"data": "DriverLicenseNumber", "className": "cell-license-number"},
            {"data": "ShipDate", "className": "cell-shipped"},
            {"data": "SwapDate", "className": "cell-swapped"},
            {"data": "DealerName", "className": "cell-dealer-name"},
          ],
          "order": [[3, "desc"], [1, "asc"]],
          "dom": "Bfrti",
          "buttons": [
            {
              "extend": "excel",
              "className": "btn-warning",
              "text": "<i class='far fa-file-excel mr-2'></i> Excel",
              "filename": slug+'Resolved',
              "messageTop": null,
              "title": null,
            }
          ],
          "language": {
            "emptyTable": "No data to display"
          }
        });
        resolvedRendered = true;
      } else if ('#removed' === $(e.target).attr('href') && !removedRendered) {
        removedTable = $("#removed-table").DataTable({
          "data": removedData,
          "scrollY": 600,
          "scroller": true,
          "columns": [
            {"data": "DriverName", "className": "cell-driver-name"},
            {"data": "DriverLicenseNumber", "className": "cell-license-number"},
            {"data": "RemovalDate", "className": "cell-removal"},
            {"data": "DealerName", "className": "cell-dealer-name"},
          ],
          "order": [[2, "desc"], [1, "asc"]],
          "dom": "Bfrti",
          "buttons": [
            {
              "extend": "excel",
              "className": "btn-warning",
              "text": "<i class='far fa-file-excel mr-2'></i> Excel",
              "filename": slug+'Removed',
              "messageTop": null,
              "title": null,
            }
          ],
          "language": {
            "emptyTable": "No data to display"
          }
        });
        removedRendered = true;
      }
    });
  });
}(jQuery, window, document));