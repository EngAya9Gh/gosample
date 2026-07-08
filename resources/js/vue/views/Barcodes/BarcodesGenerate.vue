<script setup>
import { ref, computed, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import JsBarcode from 'jsbarcode';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';

const props = defineProps({
  type: String,
  start: Number,
  sequence: Number,
  show: Boolean,
});

const form = useForm({
  type: props.type || 'bag',
  range: props.sequence || 10,
});

function submit() {
  form.post('/app/admin/barcodes/generate', {
    preserveScroll: true,
  });
}

// Generate the array of barcode values to render
const generatedBarcodes = computed(() => {
  if (!props.show) return [];
  const list = [];
  for (let i = props.start; i < props.start + props.sequence; i++) {
    if (props.type === 'bag') {
      list.push({
        value: `${i}-bag`,
        width: 6,
        height: 280,
      });
    } else {
      list.push({
        value: String(i).padStart(10, '0'),
        width: 4,
        height: 55,
      });
    }
  }
  return list;
});

// A small component-like render function for the SVGs using a custom directive
const vBarcode = {
  mounted(el, binding) {
    JsBarcode(el, binding.value.value, {
      format: "CODE128",
      width: binding.value.width,
      height: binding.value.height,
      displayValue: true,
      fontSize: 15,
      margin: 10,
    });
  },
  updated(el, binding) {
    JsBarcode(el, binding.value.value, {
      format: "CODE128",
      width: binding.value.width,
      height: binding.value.height,
      displayValue: true,
      fontSize: 15,
      margin: 10,
    });
  }
};

function printReport() {
  const prtContent = document.getElementById('barcode_area');
  if (!prtContent) return;
  const WinPrint = window.open('', '', 'width=800,height=600');
  WinPrint.document.write(`
    <html>
      <head>
        <title>Print Barcodes</title>
        <style>
          @page {
            size: auto;
            margin-top: 0.04in;
            margin-left: 0.68in;
            margin-right: 0.56in;
            margin-bottom: 0.03in;
          }
          body {
            margin: 0;
            padding: 0;
          }
          #print-area {
            width: 100%;
            margin-top: 50px;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
          }
          svg {
            margin-top: 180px;
            display: block;
            margin-left: auto;
            margin-right: auto;
          }
        </style>
      </head>
      <body>
        <div id="print-area">
          ${prtContent.innerHTML}
        </div>
      </body>
    </html>
  `);
  WinPrint.document.close();
  WinPrint.focus();
  
  // Wait for images/SVGs to render before printing
  setTimeout(() => {
    WinPrint.print();
    WinPrint.close();
  }, 250);
}
</script>

<template>
  <div>
    <Breadcrumb title="Generate Barcodes" :trail="[{ label: 'Barcodes' }, { label: 'Generate' }]" />

    <BaseCard title="Generate Barcodes">
      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <FormInput
            v-model="form.range"
            type="number"
            label="Total Count"
            placeholder="How many barcodes to generate?"
            :error="form.errors.range"
            required
            min="1"
          />
          
          <FormSelect
            v-model="form.type"
            label="Type"
            :options="[
              { value: 'bag', label: 'Bag' },
              { value: 'sample', label: 'Sample' }
            ]"
            :error="form.errors.type"
            required
          />
        </div>

        <div class="flex justify-end gap-2 mt-4">
          <BaseButton 
            v-if="generatedBarcodes.length > 0"
            type="button" 
            variant="light" 
            icon="ri-printer-line" 
            @click="printReport"
          >
            Print Barcode
          </BaseButton>
          <BaseButton 
            type="submit" 
            icon="ri-barcode-box-line"
            :loading="form.processing"
          >
            Generate Barcode
          </BaseButton>
        </div>
      </form>
    </BaseCard>

    <div v-if="generatedBarcodes.length > 0" class="mt-6 bg-white dark:bg-slate-800 p-6 rounded-lg shadow-sm">
      <div id="barcode_area" class="text-center flex flex-col gap-6 items-center">
        <div v-for="(bc, idx) in generatedBarcodes" :key="idx" :class="type === 'sample' ? 'pt-5' : 'pt-2'">
          <svg v-barcode="bc"></svg>
        </div>
      </div>
    </div>
  </div>
</template>
