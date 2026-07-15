<script setup>
/** /admin/attendances/create — New Attendance. Mock submit. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import { useToast } from '../../composables/useToast';

const { push } = useToast();
const opt = (a) => a.map(v => ({ value: v, label: v }));
const saving = ref(false);
const form = ref({"driver":"","shift":"","checkin":"","checkout":"","delay_minutes":"","overtime_minutes":""});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Attendance created'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="New Attendance" :trail="[{ label:'Attendances', href:'#/admin/attendances' }, { label:'Create' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Attendance details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class=""><FormSelect v-model="form.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])"  :required="true"  /></div>
        <div class=""><FormSelect v-model="form.shift" label="Shift" :options="opt(['Shift 1 (08:00–16:00)','Shift 2 (16:00–00:00)'])"   helper="Populated from driver shifts" /></div>
        <div class=""><FormInput v-model="form.checkin" label="Check-in Time" type="time"     /></div>
        <div class=""><FormInput v-model="form.checkout" label="Check-out Time" type="time"     /></div>
        <div class=""><FormInput v-model="form.delay_minutes" label="Delay" type="number" unit="min"    /></div>
        <div class=""><FormInput v-model="form.overtime_minutes" label="Overtime" type="number" unit="min"    /></div>
        </div>
        <template #footer>
          <div class="flex items-center justify-end gap-2">
            <BaseButton variant="light" type="button">Cancel</BaseButton>
            <BaseButton variant="primary" type="submit" icon="ri-save-line" :loading="saving">Save</BaseButton>
          </div>
        </template>
      </BaseCard>
    </form>
  </div>
</template>
