<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import FormSelect from '../../components/FormSelect.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast(); const opt=a=>a.map(v=>({value:v,label:v}));
const users=ref([
  { id:1, name:'Sara Al-Otaibi', email:'sara@mtc.sa', owner:true },
  { id:2, name:'Mohammed Admin', email:'m.admin@mtc.sa' },
  { id:3, name:'Khalid Ops', email:'k.ops@mtc.sa', deleted:true },
]);
const pick=ref('');
function remove(u){ if(u.owner)return; users.value=users.value.filter(x=>x!==u); push({type:'success',title:'Removed'}); }
function add(){ if(pick.value){ users.value.push({id:Date.now(),name:pick.value,email:pick.value.toLowerCase().replace(' ','.')+'@mtc.sa'}); push({type:'success',title:'Added'}); pick.value=''; } }
</script>
<template>
  <div>
    <Breadcrumb title="Delete Permissions" :trail="[{label:'Settings'},{label:'Delete Permissions'}]" />
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <BaseCard title="Users Allowed to Delete" icon="ri-shield-check-line" class="lg:col-span-2" :padded="false">
        <div class="divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="u in users" :key="u.id" class="flex items-center gap-3 px-5 py-3.5">
            <BaseAvatar :name="u.name" :size="38" />
            <div class="flex-1 min-w-0"><div class="font-medium text-ink dark:text-slate-100 flex items-center gap-2">{{ u.name }}<span v-if="u.owner" class="text-[10px] font-bold text-primary-700 bg-primary-50 dark:bg-primary-500/15 dark:text-primary-300 px-1.5 rounded">OWNER</span><span v-if="u.deleted" class="text-[10px] font-bold text-danger bg-danger/10 px-1.5 rounded">DELETED</span></div><div class="text-xs text-slate-400">{{ u.email }}</div></div>
            <button v-if="!u.owner" @click="remove(u)" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-danger bg-danger/10 hover:bg-danger/20 text-[13px] font-medium"><i class="ri-close-line"></i>Remove</button>
          </div>
        </div>
      </BaseCard>
      <BaseCard title="Add User" icon="ri-user-add-line">
        <FormSelect v-model="pick" label="Select user" :options="opt(['Noura Faisal','Abdullah Zahrani','Layla Ghamdi'])" class="mb-3" />
        <BaseButton variant="primary" block icon="ri-add-line" @click="add">Add</BaseButton>
      </BaseCard>
    </div>
  </div>
</template>
