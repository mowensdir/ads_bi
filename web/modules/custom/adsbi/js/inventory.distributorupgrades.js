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
        '<td>Territory:</td>' +
        '<td>'+d['tName']+'</td>' +
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
    var resolvedRendered = false,
        removedRendered  = false,
        dealerbdRendered = false,
        resolvedTable    = null,
        removedTable     = null,
        dealerbdTable    = null;

    var noncomplianceTable = $("#noncompliance-table").DataTable({
      "data": unresolvedData,
      "scrollY": 600,
      "deferRender": true,
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
          "data": "statusVM",
          "className": "cell-vm-status"
        },
        { // 4
          "data": "shipped",
          "className": "cell-shipped"
        },
        { // 5
          "data": "lastService",
          "className": "cell-last-service"
        },
        { // 6
          "data": "daysSince",
          "className": "cell-days-since"
        },
        { // 7
          "data": "lastHH",
          "className": "cell-last-hh"
        },
        { // 8
          "data": "lastVM",
          "className": "cell-last-vm"
        },
        { // 9
          "data": "nextService",
          "className": "cell-next-service"
        },
        { // 10
          "data": "tState",
          "className": "cell-territory-state"
        },
        { // 11
          "data": "deName",
          "className": "cell-dealer-name"
        },
        { // 12
          "data": "id",
          "title": "Driver ID",
          "visible": false,
          "searchable": false
        },
        { // 13
          "data": "drDLN",
          "title": "Driver License Number",
          "visible": false
        },
        { // 14
          "data": "drPhone",
          "title": "Driver Phone",
          "visible": false,
          "searchable": false
        },
        { // 15
          "data": "drEmail",
          "title": "Driver Email",
          "visible": false,
          "searchable": false
        },
        { // 16
          "data": "install",
          "title": "Install Date",
          "visible": false,
          "searchable": false
        },
        { // 17
          "data": "hhsn",
          "title": "HH Assigned",
          "visible": false
        },
        { // 18
          "data": "vmsn",
          "title": "VM Assigned",
          "visible": false
        },
        { // 19
          "data": "preassigned",
          "title": "Preassigned",
          "visible": false,
          "searchable": false
        },
      ],
      "order": [[6, "asc"], [10, "asc"]],
      "dom": "Bfrti",
      "buttons": [
        {
          "extend": "excel",
          "className": "btn-warning",
          "text": "<i class='far fa-file-excel mr-2'></i> Excel",
          "filename": "DistributorUpgradesUnresolved",
          "messageTop": null,
          "title": null,
          "exportOptions": {
            "columns": [12, 1, 13, 2, 3, 14, 15, 11, 16, 17, 18, 19, 4, 5, 7, 8, 9, 10]
          }
        }
      ],
      "createdRow": function(row, data, dataIndex, cells) {
        var days = parseInt(data.daysSince);
        if (60 <= days && days < 100) {
          $(row).find(".cell-days-since").first().addClass("table-warning");
        } else if (100 < days) {
          $(row).find(".cell-days-since").first().addClass("table-danger");
        }
      }
    });

    $("#noncompliance-table tbody").on("click", "td.details-control", function() {
      var tr = $(this).closest("tr");
      var row = noncomplianceTable.row(tr);

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

    $("a[data-toggle='tab']").on("shown.bs.tab", function(e) {
      if ('#resolved' === $(e.target).attr('href') && !resolvedRendered) {
        resolvedTable = $("#resolved-table").DataTable({
          "data": resolvedData,
          "scrollY": 600,
          "deferRender": true,
          "scroller": true,
          "columns": [
            {"data": "DriverName", "className": "cell-driver-name"},
            {"data": "DriverLicenseNumber", "className": "cell-license-number"},
            {"data": "ShipDate", "className": "cell-shipped"},
            {"data": "ComplianceDate", "className": "cell-resolved"},
            {"data": "TerritoryState", "className": "cell-territory-state"},
            {"data": "DealerName", "className": "cell-dealer-name"},
          ],
          "order": [[3, "desc"], [4, "asc"], [1, "asc"]],
          "dom": "Bfrti",
          "buttons": [
            {
              "extend": "excel",
              "className": "btn-warning",
              "text": "<i class='far fa-file-excel mr-2'></i> Excel",
              "filename": "DistributorUpgradesResolved",
              "messageTop": null,
              "title": null,
            }
          ]
        });
        resolvedRendered = true;
      } else if ('#removed' === $(e.target).attr('href') && !removedRendered) {
        removedTable = $("#removed-table").DataTable({
          "data": removedData,
          "scrollY": 600,
          "deferRender": true,
          "scroller": true,
          "columns": [
            {"data": "DriverName", "className": "cell-driver-name"},
            {"data": "DriverLicenseNumber", "className": "cell-license-number"},
            {"data": "RemovalDate", "className": "cell-removed"},
            {"data": "TerritoryState", "className": "cell-territory-state"},
            {"data": "DealerName", "className": "cell-dealer-name"},
          ],
          "order": [[2, "desc"], [3, "asc"], [1, "asc"]],
          "dom": "Bfrti",
          "buttons": [
            {
              "extend": "excel",
              "className": "btn-warning",
              "text": "<i class='far fa-file-excel mr-2'></i> Excel",
              "filename": "DistributorUpgradesRemoved",
              "messageTop": null,
              "title": null,
            }
          ]
        });
        removedRendered = true;
      } else if ('#dealerbd' === $(e.target).attr('href') && !dealerbdRendered) {
        dealerbdTable = $('#dealerbd-table').DataTable({
          "data": dealerbdData,
          "columns": [
            {"data": "dealer"},
            {"data": "drivers"},
            {"data": "unresolved"},
            {"data": "shipped"},
            {"data": "resolved"},
            {"data": "removed"},
            {"data": "nchhvm"},
            {"data": "nchh"},
            {"data": "ncvm"},
            {"data": "progress"},
          ],
          "order": [[0, "asc"]],
          "paging": false,
          "searching": false,
          "info": false,
          "dom": "rtB",
          "buttons": [
            {
              "extend": "excel",
              "className": "btn-warning",
              "text": "<i class='far fa-file-excel mr-2'></i> Excel",
              "filename": "DistributorUpgradesDealerBreakdown",
              "messageTop": null,
              "title": null,
            }
          ]
        });
        dealerbdRendered = true;
      }
    });
  });
}(jQuery, window, document));