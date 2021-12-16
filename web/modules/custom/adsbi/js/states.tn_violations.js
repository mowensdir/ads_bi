(function ($, window, document) {
  $(function () {
    var exceptionsRendered = false,
        reportedRendered   = false,
        ignoredRendered    = false,
        exceptionsTable    = null,
        reportedTable      = null,
        ignoredTable       = null;

    if (typeof pendingData !== 'undefined') {
      var pendingTable = $("#tnviolations-pending").DataTable({
        "data": pendingData,
        "scrollY": 600,
        "deferRender": true,
        "scroller": true,
        "columns": [
          {"data": "CustomerName"},
          {"data": "DLN"},
          {"data": "ViolationTimestamp", "searchable": false},
          {"data": "Violation", "searchable": false},
          {"data": "ViolationStatus"},
          {"data": "reviewlink", "className": "cell-review-link", "searchable": false, "render": function(data, type, row, meta) {
            return '<button type="button" class="btn btn-link btn-sm" data-toggle="modal" data-target="#reviewModal" ' +
                   'data-row="' + meta.row + '" data-name="' + row.CustomerName + '" data-code="' + row.ViolationCode + '" ' +
                   'data-did="' + row.DriverID + '" data-time="' + row.ViolationTimestamp + '">Review</button>';
          }},
          {"data": "DriverID", "visible": false, "searchable": false}
        ],
        "order": [[6, "asc"], [2, "asc"]],
        "language": {
          "emptyTable": "No pending violations found"
        }
      });
    }

    $("a[data-toggle='tab']").on("shown.bs.tab", function(e) {
      if ('#exceptions' === $(e.target).attr('href') && !exceptionsRendered) {
        var exceptionsTable = $("#tnviolations-exceptions").DataTable({
          "data": exceptionsData,
          "scrollY": 600,
          "deferRender": true,
          "scroller": true,
          "columns": [
            {"data": "CustomerName"},
            {"data": "DLN"},
            {"data": "ViolationTimestamp", "searchable": false},
            {"data": "Violation", "searchable": false},
            {"data": "ViolationStatus"},
            {"data": "Rejected"},
            {"data": "Exception"},
            {"data": "action", "className": "cell-exception-action", "searchable":false, "render": function(data, type, row, meta) {
              if ('Exception' === row.ViolationStatus) {
                return '<button type="button" class="btn btn-link btn-sm resend-btn" ' + 'data-row="' + meta.row + '" data-code="' +
                       row.ViolationCode + '" ' + 'data-did="' + row.DriverID + '" data-time="' + row.ViolationTimestamp + '">Resend</button>';
              } else {
                return '<button type="button" class="btn btn-link btn-sm unqueue-btn" ' + 'data-row="' + meta.row + '" data-code="' +
                       row.ViolationCode + '" ' + 'data-did="' + row.DriverID + '" data-time="' + row.ViolationTimestamp + '">Unqueue</button>';
              }
            }},
            {"data": "TennesseeViolationID", "visible": false, "searchable": false}
          ],
          "order": [[8, "desc"]]
        });
        exceptionsRendered = true;
      } else if ('#reported' === $(e.target).attr('href') && !reportedRendered) {
        var reportedTable = $("#tnviolations-reported").DataTable({
          "data": reportedData,
          "scrollY": 600,
          "deferRender": true,
          "scroller": true,
          "columns": [
            {"data": "CustomerName"},
            {"data": "DLN"},
            {"data": "ViolationTimestamp", "searchable": false},
            {"data": "Violation", "searchable": false},
            {"data": "ViolationStatus"},
            {"data": "TennesseeViolationID", "visible": false, "searchable": false}
          ],
          "order": [[5, "desc"]]
        });
        reportedRendered = true;
      } else if ('#ignored' === $(e.target).attr('href') && !ignoredRendered) {
        var ignoredTable = $("#tnviolations-ignored").DataTable({
          "data": ignoredData,
          "scrollY": 600,
          "deferRender": true,
          "scroller": true,
          "columns": [
            {"data": "CustomerName"},
            {"data": "DLN"},
            {"data": "ViolationTimestamp", "searchable": false},
            {"data": "Violation", "searchable": false},
            {"data": "ViolationStatus"},
            {"data": "action", "className": "cell-exception-action", "searchable":false, "render": function(data, type, row, meta) {
              return '<button type="button" class="btn btn-link btn-sm unignore-btn" ' + 'data-row="' + meta.row + '" data-code="' +
                     row.ViolationCode + '" ' + 'data-did="' + row.DriverID + '" data-time="' + row.ViolationTimestamp + '">Unignore</button>';
            }},
            {"data": "TennesseeViolationID", "visible": false, "searchable": false}
          ],
          "order": [[6, "desc"]]
        });
        ignoredRendered = true;
      }
    });

    $("#approveBtn").click(function() {
      var button = $(this);
      var table  = $("#tnviolations-pending").DataTable();
      var row    = button.attr("data-row");
      var code   = button.attr("data-code");
      var did    = button.attr("data-did");
      var time   = button.attr("data-time");

      $.post('/data/states/uptnviolationstatus/' + did + '/' + time + '/' + code + '/Approved', function(data) {
        table.cell({"row": parseInt(row), "column": 4}).data('Queued').draw();
      });

      $("#reviewModal").modal('hide');
    });

    $("#ignoreBtn").click(function() {
      var button = $(this);
      var table  = $("#tnviolations-pending").DataTable();
      var row    = button.attr("data-row");
      var code   = button.attr("data-code");
      var did    = button.attr("data-did");
      var time   = button.attr("data-time");

      $.post('/data/states/uptnviolationstatus/' + did + '/' + time + '/' + code + '/Ignored', function(data) {
        table.cell({"row": parseInt(row), "column": 4}).data('Ignored').draw();
      });

      $("#reviewModal").modal('hide');
    });
  });

  $("#reviewModal").on("show.bs.modal", function(e) {
    var button = $(e.relatedTarget) // Button that triggered the modal
    var row    = button.data("row");
    var name   = button.data("name");
    var code   = button.data("code");
    var did    = button.data("did");
    var time   = button.data("time");
    var modal  = $(this);

    modal.find(".modal-title").text(name + ' | ' + code + ' | ' + time);
    modal.find("#approveBtn").attr("data-row", row);
    modal.find("#approveBtn").attr("data-did", did);
    modal.find("#approveBtn").attr("data-code", code);
    modal.find("#approveBtn").attr("data-time", time);
    modal.find("#ignoreBtn").attr("data-row", row);
    modal.find("#ignoreBtn").attr("data-did", did);
    modal.find("#ignoreBtn").attr("data-code", code);
    modal.find("#ignoreBtn").attr("data-time", time);
    
    $.ajax({
      "url": '/data/states/startcollar/' + did + '/' + time + '/' + code
    }).done(function(data) {
      modal.find(".modal-body").html('<pre>' + data.data[0] + '</pre>');
    });
  });

  $("#reviewModal").on("hidden.bs.modal", function(e) {
    var modal  = $(this);

    modal.find(".modal-title").text('');
    modal.find(".modal-body").html('');
    modal.find("#approveBtn").attr("data-row", '');
    modal.find("#approveBtn").attr("data-did", '');
    modal.find("#approveBtn").attr("data-code", '');
    modal.find("#approveBtn").attr("data-time", '');
    modal.find("#ignoreBtn").attr("data-row", '');
    modal.find("#ignoreBtn").attr("data-did", '');
    modal.find("#ignoreBtn").attr("data-code", '');
    modal.find("#ignoreBtn").attr("data-time", '');
  });

  $("#tnviolations-exceptions").on("click", ".resend-btn", function(e) {
    var button = $(this);
    var table  = $("#tnviolations-exceptions").DataTable();
    var row    = button.attr("data-row");
    var code   = button.attr("data-code");
    var did    = button.attr("data-did");
    var time   = button.attr("data-time");

    $.post('/data/states/uptnviolationstatus/' + did + '/' + time + '/' + code + '/Resend', function(data) {
      table.cell({"row": parseInt(row), "column": 4}).data('Queued').draw();

      button.removeClass("resend-btn");
      button.addClass("unqueue-btn");
      button.html("Unqueue");
    });
  });

  $("#tnviolations-exceptions").on("click", ".unqueue-btn", function(e) {
    var button = $(this);
    var table  = $("#tnviolations-exceptions").DataTable();
    var row    = button.attr("data-row");
    var code   = button.attr("data-code");
    var did    = button.attr("data-did");
    var time   = button.attr("data-time");

    $.post('/data/states/uptnviolationstatus/' + did + '/' + time + '/' + code + '/Exception', function(data) {
      table.cell({"row": parseInt(row), "column": 4}).data('Exception').draw();

      button.removeClass("unqueue-btn");
      button.addClass("resend-btn");
      button.html("Resend");
    });
  });

  $("#tnviolations-ignored").on("click", ".unignore-btn", function(e) {
    var button = $(this);
    var table  = $("#tnviolations-ignored").DataTable();
    var row    = button.attr("data-row");
    var code   = button.attr("data-code");
    var did    = button.attr("data-did");
    var time   = button.attr("data-time");
    
    $.post('/data/states/uptnviolationstatus/' + did + '/' + time + '/' + code + '/New', function(data) {
      table.cell({"row": parseInt(row), "column": 4}).data('New').draw();

      button.removeClass("unignore-btn");
      button.addClass("ignore-btn");
      button.html("Ignore");
    });
  });

  $("#tnviolations-ignored").on("click", ".ignore-btn", function(e) {
    var button = $(this);
    var table  = $("#tnviolations-ignored").DataTable();
    var row    = button.attr("data-row");
    var code   = button.attr("data-code");
    var did    = button.attr("data-did");
    var time   = button.attr("data-time");
    
    $.post('/data/states/uptnviolationstatus/' + did + '/' + time + '/' + code + '/Ignored', function(data) {
      table.cell({"row": parseInt(row), "column": 4}).data('Ignored').draw();

      button.removeClass("ignore-btn");
      button.addClass("unignore-btn");
      button.html("Unignore");
    });
  });
}(jQuery, window, document));
