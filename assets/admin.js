jQuery(document).ready(function ($) {
  const globalAttributes = $(".global_attribute_table");
  let uniqueId = Math.random().toString(36).slice(2, 11);

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

  // add global attribute
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
	<tr>
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
			<input type="checkbox" name="woonuxt_options[global_attributes][${uniqueId}][hideEmpty]"value="1" />
		</td>
		<td>
			<input type="checkbox" name="woonuxt_options[global_attributes][${uniqueId}][openByDefault]" value="1"  />
		</td>
		<td>
			<div class="text-right row-actions">
				<a href="#" class="text-danger remove_global_attribute">Delete</a> |
				<a href="#" title="Move Up" class="text-primary move_global_attribute_up">▲</a> |
				<a href="#" title="Move Down" class="text-primary move_global_attribute_down">▼</a>
			</div>
		</td>
	</tr>
`;

      globalAttributes.find("tbody").append(newAttribute);
      uniqueId = Math.random().toString(36).slice(2, 11);
    } catch (error) {
      console.error("Error adding global attribute:", error);
      alert(
        "An error occurred while adding the global attribute. Please try again."
      );
    }
  });

  // remove global attribute
  $(document).on("click", ".remove_global_attribute", function (e) {
    e.preventDefault();
    try {
      if (confirm("Are you sure you want to remove this global attribute?")) {
        $(this).closest("tr").remove();
      }
    } catch (error) {
      console.error("Error removing global attribute:", error);
      alert("An error occurred while removing the global attribute.");
    }
  });

  // move global attribute up
  $(document).on("click", ".move_global_attribute_up", function (e) {
    e.preventDefault();
    try {
      const currentRow = $(this).closest("tr");
      const prevRow = currentRow.prev();
      if (prevRow.length && !prevRow.hasClass("global_attribute_header")) {
        currentRow.insertBefore(prevRow);
      }
    } catch (error) {
      console.error("Error moving global attribute up:", error);
    }
  });

  // move global attribute down
  $(document).on("click", ".move_global_attribute_down", function (e) {
    e.preventDefault();
    try {
      const currentRow = $(this).closest("tr");
      const nextRow = currentRow.next();
      if (nextRow.length && !nextRow.hasClass("global_attribute_footer")) {
        currentRow.insertAfter(nextRow);
      }
    } catch (error) {
      console.error("Error moving global attribute down:", error);
    }
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
});
