(function ($, window, document) {
  $(function () {
    var resolvedRendered = false,
        removedRendered  = false,
        statebdRendered  = false,
        distrobdRendered = false,
        resolvedTable    = null,
        removedTable     = null,
        statebdTable     = null,
        distrobdTable    = null;

    var unresolvedTable = $("#unresolved-table").DataTable({
      "data": unresolvedData,
      "scrollY": 600,
      "scroller": true,
      "columns": [
        { // 0
          "data": "id",
          "className": "cell-driver-id"
        },
        { // 1
          "data": "drName",
          "className": "cell-driver-name"
        },
        { // 2
          "data": "vmsn",
          "className": "cell-vm"
        },
        { // 3
          "data": "lastService",
          "className": "cell-last-service"
        },
        { // 4
          "data": "svcDue",
          "className": "cell-svc-due"
        },
        { // 5
          "data": "daysLate",
          "className": "cell-days-late"
        },
        { // 6
          "data": "shipped",
          "className": "cell-shipped"
        },
        { // 7
          "data": "nextService",
          "className": "cell-next-service"
        },
        { // 8
          "data": "tState",
          "className": "cell-state"
        },
        { // 9
          "data": "deName",
          "className": "cell-service-center"
        },
        { // 10
          "data": "drDLN",
          "title": "Driver License Number",
          "visible": false,
          "searchable": false
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
      ],
      "order": [[7, "asc"]],
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
            "columns": [0, 1, 10, 2, 11, 12, 9, 13, 6, 3, 4, 7]
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

    $("a[data-toggle='tab']").on("shown.bs.tab", function(e) {
      if ('#resolved' === $(e.target).attr('href') && !resolvedRendered) {
        resolvedTable = $("#resolved-table").DataTable({
          "data": resolvedData,
          "scrollY": 600,
          "scroller": true,
          "columns": [
            {"data": "DriverID", "className": "cell-driver-id"},
            {"data": "DriverName", "className": "cell-driver-name"},
            {"data": "ShipDate", "className": "cell-shipped"},
            {"data": "ComplianceDate", "className": "cell-compliance-date"},
            {"data": "TerritoryState", "className": "cell-state"},
            {"data": "DealerName", "className": "cell-service-center"},
            {"data": "DriverLicenseNumber", "title": "Driver License Number", "visible": false, "searchable": false},
          ],
          "order": [[3, "desc"], [0, "asc"]],
          "dom": "Bfrti",
          "buttons": [
            {
              "extend": "excel",
              "className": "btn-warning",
              "text": "<i class='far fa-file-excel mr-2'></i> Excel",
              "filename": slug+'Resolved',
              "messageTop": null,
              "title": null,
              "exportOptions": {
                "columns": [0, 1, 6, 2, 3, 4, 5]
              }
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
            {"data": "DriverID", "className": "cell-driver-id"},
            {"data": "DriverName", "className": "cell-driver-name"},
            {"data": "RemovalDate", "className": "cell-removal-date"},
            {"data": "TerritoryState", "className": "cell-state"},
            {"data": "DealerName", "className": "cell-service-center"},
            {"data": "DriverLicenseNumber", "title": "Driver License Number", "visible": false, "searchable": false},
          ],
          "order": [[2, "desc"], [0, "asc"]],
          "dom": "Bfrti",
          "buttons": [
            {
              "extend": "excel",
              "className": "btn-warning",
              "text": "<i class='far fa-file-excel mr-2'></i> Excel",
              "filename": slug+'Resolved',
              "messageTop": null,
              "title": null,
              "exportOptions": {
                "columns": [0, 1, 5, 2, 3, 4]
              }
            }
          ],
          "language": {
            "emptyTable": "No data to display"
          }
        });
        removedRendered = true;
      } else if ('#statebd' === $(e.target).attr('href') && !statebdRendered) {
        statebdTable = $("#statebd-table").DataTable({
          "data": statebdData,
          "scrollY": 600,
          "scroller": true,
          "columns": [
            {"data": "state"},
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
              "filename": slug+'StateBreakdown',
              "messageTop": null,
              "title": null,
            }
          ],
          "language": {
            "emptyTable": "No data to display"
          }
        });
        statebdRendered  = true;
      } else if ('#distrobd' === $(e.target).attr('href') && !distrobdRendered) {
        distrobdTable = $("#distrobd-table").DataTable({
          "data": distrobdData,
          "scrollY": 600,
          "scroller": true,
          "columns": [
            {"data": "distro"},
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
              "filename": slug+'DistributorBreakdown',
              "messageTop": null,
              "title": null,
            }
          ],
          "language": {
            "emptyTable": "No data to display"
          }
        });
        distrobdRendered  = true;
      }
    });
  });
}(jQuery, window, document));