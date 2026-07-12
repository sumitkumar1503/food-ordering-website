document.addEventListener("DOMContentLoaded", () => {
  if (document.body.classList.contains("customer-dashboard-page")) {
    if ("scrollRestoration" in history) history.scrollRestoration = "manual";
    window.scrollTo(0, 0);
    setTimeout(() => window.scrollTo(0, 0), 50);
  }

  const formatMoney = (value) =>
    `₹${Math.round(value).toLocaleString("en-IN")}`;

  const updateCartTotals = () => {
    const cart = document.querySelector("[data-cart]");
    if (!cart) return;
    let subtotal = 0;
    let count = 0;
    cart.querySelectorAll("[data-cart-item]").forEach((item) => {
      const quantity = Number(item.querySelector(".quantity-picker input").value);
      const unitPrice = Number(item.dataset.unitPrice);
      const lineTotal = quantity * unitPrice;
      subtotal += lineTotal;
      count += quantity;
      item.querySelector("[data-line-total]").textContent = formatMoney(lineTotal);
    });
    const discount = Math.min(Number(cart.dataset.discount), subtotal);
    const delivery =
      subtotal === 0 || subtotal >= Number(cart.dataset.freeDelivery)
        ? 0
        : Number(cart.dataset.deliveryFee);
    const tax = (subtotal - discount) * (Number(cart.dataset.taxRate) / 100);
    const total = Math.max(0, subtotal - discount + delivery + tax);
    cart.querySelector("[data-cart-count]").textContent = count;
    cart.querySelector("[data-cart-subtotal]").textContent = formatMoney(subtotal);
    const deliveryElement = cart.querySelector("[data-cart-delivery]");
    deliveryElement.textContent = delivery ? formatMoney(delivery) : "FREE";
    deliveryElement.classList.toggle("text-success", delivery === 0);
    cart.querySelector("[data-cart-tax]").textContent = formatMoney(tax);
    cart.querySelector("[data-cart-total]").textContent = formatMoney(total);
  };

  document.querySelectorAll("[data-qty]").forEach((button) => {
    button.addEventListener("click", () => {
      const input = button.parentElement.querySelector("input");
      const next = Math.max(1, Math.min(10, Number(input.value) + Number(button.dataset.qty)));
      input.value = next;
      updateCartTotals();
    });
  });

  document.querySelectorAll("[data-copy]").forEach((button) => {
    button.addEventListener("click", async (event) => {
      event.preventDefault();
      try {
        await navigator.clipboard.writeText(button.dataset.copy);
        const original = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check2"></i>';
        setTimeout(() => (button.innerHTML = original), 1300);
      } catch {
        window.prompt("Copy this coupon code:", button.dataset.copy);
      }
    });
  });

  document.querySelectorAll("[data-password]").forEach((button) => {
    button.addEventListener("click", () => {
      const input = button.parentElement.querySelector("input");
      input.type = input.type === "password" ? "text" : "password";
      button.innerHTML = `<i class="bi bi-eye${input.type === "password" ? "" : "-slash"}"></i>`;
    });
  });

  const sidebarButton = document.querySelector("[data-sidebar]");
  if (sidebarButton) {
    sidebarButton.addEventListener("click", () => document.querySelector("#sidebar").classList.toggle("open"));
  }

  const clock = document.querySelector("[data-clock]");
  if (clock) {
    const updateClock = () => {
      clock.textContent = new Intl.DateTimeFormat("en-IN", {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
      }).format(new Date());
    };
    updateClock();
    setInterval(updateClock, 1000);
  }

  document.querySelectorAll(".toast-flash").forEach((toast) => {
    setTimeout(() => {
      toast.style.transition = ".3s";
      toast.style.opacity = "0";
      toast.style.transform = "translateY(-10px)";
      setTimeout(() => toast.remove(), 350);
    }, 4200);
  });

  document.querySelectorAll(".status-form select").forEach((select) => {
    select.dataset.previous = select.value;
    select.addEventListener("change", () => {
      if (window.confirm(`Change order status to “${select.options[select.selectedIndex].text}”?`)) {
        select.closest("form").submit();
      } else {
        select.value = select.dataset.previous;
      }
    });
  });

  document.querySelectorAll("[data-confirm]").forEach((form) => {
    form.addEventListener("submit", (event) => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });

  document.querySelectorAll("[data-toggle-target]").forEach((button) => {
    button.addEventListener("click", () => {
      const target = document.querySelector(button.dataset.toggleTarget);
      if (!target) return;
      target.classList.toggle("d-none");
      if (!target.classList.contains("d-none")) target.querySelector("textarea, input")?.focus();
    });
  });

  const queueFilters = document.querySelector("[data-queue-filters]");
  if (queueFilters) {
    queueFilters.querySelectorAll("[data-filter]").forEach((button) => {
      button.addEventListener("click", () => {
        queueFilters.querySelectorAll("button").forEach((item) => item.classList.remove("active"));
        button.classList.add("active");
        document.querySelectorAll(".kitchen-ticket").forEach((ticket) => {
          ticket.hidden = button.dataset.filter !== "all" && !ticket.classList.contains(button.dataset.filter);
        });
      });
    });
  }

  const imageInput = document.querySelector('input[name="image_upload"]');
  if (imageInput) {
    imageInput.addEventListener("change", () => {
      const file = imageInput.files[0];
      let preview = document.querySelector(".upload-preview");
      if (!preview) {
        preview = document.createElement("img");
        preview.className = "upload-preview";
        imageInput.parentElement.appendChild(preview);
      }
      if (file) preview.src = URL.createObjectURL(file);
    });
  }

  document.querySelectorAll('input[name="order_type"]').forEach((radio) => {
    radio.addEventListener("change", () => {
      const address = document.querySelector('textarea[name="address"]');
      if (!address) return;
      const isDelivery = radio.value === "delivery" && radio.checked;
      if (radio.checked) {
        address.required = isDelivery;
        address.closest(".col-12").classList.toggle("d-none", !isDelivery);
        const summary = document.querySelector("[data-checkout-summary]");
        if (summary) {
          const delivery = isDelivery ? Number(summary.dataset.delivery) : 0;
          const total = Number(summary.dataset.total) - (isDelivery ? 0 : Number(summary.dataset.delivery));
          summary.querySelector("[data-checkout-delivery]").textContent = delivery ? formatMoney(delivery) : "FREE";
          summary.querySelector("[data-checkout-total]").textContent = formatMoney(total);
        }
      }
    });
  });
});
