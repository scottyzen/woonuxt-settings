jQuery(document).ready(function ($) {
  const globalAttributes = $(".global_attribute_table");
  let uniqueId = Math.random().toString(36).slice(2, 11);

  // Tab navigation removed - using single page layout

  // product_attributes is an object that contains the product attributes

  // deploy-button FROM build_hook
  const buildUrl = $("#build_url");
  $("#deploy-button").click(function (e) {
    e.preventDefault();
    const $button = $(this);
    const originalText = $button.text();

    if (!buildUrl.val()) {
      alert("Build URL is not set.");
      return;
    }

    $button.text("Deploying...").prop("disabled", true);

    $.ajax({
      url: buildUrl.val(),
      type: "POST",
      timeout: 30000,
      success(data) {
        alert("Build triggered successfully");
      },
      error(xhr, status, error) {
        alert("Failed to trigger build: " + (xhr.responseText || error));
      },
      complete() {
        $button.text(originalText).prop("disabled", false);
      },
    });
  });

  // Add global attribute with animation
  $(".add_global_attribute").click(function (e) {
    e.preventDefault();

    try {
      // Check if product_attributes exists and has content
      if (
        typeof product_attributes === "undefined" ||
        !product_attributes ||
        Object.keys(product_attributes).length === 0
      ) {
        alert(
          "No product attributes available. Please create product attributes first."
        );
        return;
      }

      const newAttribute = `
	<tr class="adding sortable-item">
		<td class="drag-handle" style="cursor: grab;">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.4;">
				<line x1="3" y1="9" x2="21" y2="9"></line>
				<line x1="3" y1="15" x2="21" y2="15"></line>
			</svg>
		</td>
		<td>
			<input type="text" class="flex-1" name="woonuxt_options[global_attributes][${uniqueId}][label]" value="" placeholder="e.g. Filter by Color"/>
        </td>
        <td>
			<select name="woonuxt_options[global_attributes][${uniqueId}][slug]" required>
				${Object.keys(product_attributes)
          .map((key) => {
            return `<option value="pa_${product_attributes[key].attribute_name}">${product_attributes[key].attribute_label}</option>`;
          })
          .join("")}
			</select>
		</td>
		<td>
			<input type="checkbox" name="woonuxt_options[global_attributes][${uniqueId}][showCount]" value="1" />
		</td>
		<td>
			<input type="checkbox" name="woonuxt_options[global_attributes][${uniqueId}][hideEmpty]" value="1" />
		</td>
		<td>
			<input type="checkbox" name="woonuxt_options[global_attributes][${uniqueId}][openByDefault]" value="1" />
		</td>
		<td class="text-center">
			<button type="button" class="remove_global_attribute icon-button" title="Delete">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="3 6 5 6 21 6"></polyline>
					<path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
					<line x1="10" y1="11" x2="10" y2="17"></line>
					<line x1="14" y1="11" x2="14" y2="17"></line>
				</svg>
			</button>
		</td>
	</tr>
`;

      // Remove empty state if it exists
      globalAttributes.find("tbody tr.empty-state").remove();

      const $newRow = $(newAttribute);
      globalAttributes.find("tbody").append($newRow);

      // Focus on the label input
      setTimeout(() => {
        $newRow.removeClass("adding");
        $newRow.find('input[type="text"]').focus();
      }, 300);

      uniqueId = Math.random().toString(36).slice(2, 11);
    } catch (error) {
      console.error("Error adding global attribute:", error);
      alert(
        "An error occurred while adding the global attribute. Please try again."
      );
    }
  });

  // Remove global attribute - simplified with icon button
  $(document).on("click", ".remove_global_attribute", function (e) {
    e.preventDefault();

    try {
      const $button = $(this);
      const $row = $button.closest("tr");

      // Confirm deletion
      if (
        confirm(
          "Are you sure you want to delete this attribute? This action cannot be undone."
        )
      ) {
        $row.addClass("removing");
        setTimeout(() => {
          $row.remove();

          // Add empty state if no rows left
          const $tbody = globalAttributes.find("tbody");
          if ($tbody.find("tr:not(.empty-state)").length === 0) {
            $tbody.html(`
              <tr class="empty-state">
                <td colspan="7">
                  <span class="dashicons dashicons-filter" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></span>
                  No global attributes configured yet. Click "Add New" to create your first filter.
                </td>
              </tr>
            `);
          }
        }, 300);
      }
    } catch (error) {
      console.error("Error removing global attribute:", error);
      alert("An error occurred while removing the global attribute.");
    }
  });

  // ========================================
  // PREMIUM DRAG & DROP FUNCTIONALITY
  // ========================================

  // Initialize drag and drop for sortable lists
  function initDragAndDrop(tableSelector) {
    let draggedElement = null;
    let draggedIndex = null;

    const $table = $(tableSelector);
    const $tbody = $table.find("tbody");

    // Make rows draggable
    $tbody.on("dragstart", ".sortable-item", function (e) {
      draggedElement = this;
      draggedIndex = $(this).index();
      $(this).addClass("dragging");
      e.originalEvent.dataTransfer.effectAllowed = "move";
      e.originalEvent.dataTransfer.setData("text/html", this.innerHTML);
    });

    $tbody.on("dragend", ".sortable-item", function (e) {
      $(this).removeClass("dragging");
      $(".drag-over").removeClass("drag-over");
      draggedElement = null;
    });

    $tbody.on("dragover", ".sortable-item", function (e) {
      if (e.preventDefault) {
        e.preventDefault();
      }
      e.originalEvent.dataTransfer.dropEffect = "move";

      const $this = $(this);
      if (draggedElement !== this) {
        $(".drag-over").removeClass("drag-over");
        $this.addClass("drag-over");
      }
      return false;
    });

    $tbody.on("dragleave", ".sortable-item", function (e) {
      $(this).removeClass("drag-over");
    });

    $tbody.on("drop", ".sortable-item", function (e) {
      if (e.stopPropagation) {
        e.stopPropagation();
      }

      const $this = $(this);
      const targetIndex = $this.index();

      if (draggedElement !== this) {
        // Determine insert position
        if (targetIndex < draggedIndex) {
          $this.before(draggedElement);
        } else {
          $this.after(draggedElement);
        }

        // Add animation
        $(draggedElement).addClass("adding");
        setTimeout(() => {
          $(draggedElement).removeClass("adding");
        }, 300);
      }

      $this.removeClass("drag-over");
      return false;
    });

    // Make drag handles work
    $tbody.find(".drag-handle").attr("draggable", "false");
    $tbody.find(".sortable-item").attr("draggable", "true");
  }

  // Initialize drag and drop for both tables
  if ($(".woo-seo-table").length) {
    initDragAndDrop(".woo-seo-table");
  }

  if ($(".global_attribute_table").length) {
    initDragAndDrop(".global_attribute_table");
  }

  // Reinitialize when new rows are added
  $(document).on("DOMNodeInserted", ".sortable-list", function () {
    $(this).find(".sortable-item").attr("draggable", "true");
  });

  // Handle color picker
  $("#primary-color-setting input").on("change input", function () {
    try {
      const colorValue = $(this).val();

      // Basic validation for hex color
      if (colorValue && !colorValue.match(/^#[0-9a-fA-F]{6}$/)) {
        console.warn("Invalid color format:", colorValue);
        return;
      }

      $("#woonuxt_options\\[primary_color\\]").val(colorValue);
      $("#color-preview").css("background-color", colorValue);
      $("#primary_color_picker").val(colorValue);
    } catch (error) {
      console.error("Error handling color picker:", error);
    }
  });

  // WordPress Media Uploader for Logo
  let logoMediaUploader;

  $("#woonuxt-upload-logo-btn").on("click", function (e) {
    e.preventDefault();

    // If the uploader object has already been created, reopen the dialog
    if (logoMediaUploader) {
      logoMediaUploader.open();
      return;
    }

    // Create the media uploader
    logoMediaUploader = wp.media({
      title: "Choose Logo Image",
      button: {
        text: "Use this image",
      },
      multiple: false, // Only allow one image to be selected
      library: {
        type: "image", // Only show images
      },
    });

    // When an image is selected, run a callback
    logoMediaUploader.on("select", function () {
      const attachment = logoMediaUploader
        .state()
        .get("selection")
        .first()
        .toJSON();

      // Update the hidden input with the image URL
      $("#woonuxt_logo_url").val(attachment.url);

      // Update the preview
      $("#woonuxt-logo-preview img").attr("src", attachment.url);
      $("#woonuxt-logo-preview").show();

      // Show the remove button
      $("#woonuxt-remove-logo-btn").show();
    });

    // Open the uploader dialog
    logoMediaUploader.open();
  });

  // Remove logo button
  $("#woonuxt-remove-logo-btn").on("click", function (e) {
    e.preventDefault();

    // Clear the hidden input
    $("#woonuxt_logo_url").val("");

    // Hide the preview
    $("#woonuxt-logo-preview").hide();

    // Hide the remove button
    $(this).hide();
  });
});
