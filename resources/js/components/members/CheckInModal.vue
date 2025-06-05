<template>
  <a-modal
    :visible="isOpen"
    :title="`Check-in hội viên: ${member?.name || 'N/A'}`"
    @cancel="handleCancel"
    :maskClosable="false"
    :destroyOnClose="true"
  >
    <div class="space-y-4">
      <div v-if="member" class="flex items-center pb-4 border-b">
        <a-avatar :size="50" class="bg-primary mr-4">
          {{ member.name.charAt(0) }}
        </a-avatar>
        <div>
          <div class="text-lg font-medium">{{ member.name }}</div>
          <div class="text-muted-foreground">{{ member.phone }}</div>
        </div>
      </div>

      <div v-if="membershipInfo" class="mt-4">
        <div class="text-sm font-medium mb-2">Thông tin gói tập:</div>
        <a-descriptions :column="1" size="small">
          <a-descriptions-item label="Gói tập">
            {{ membershipInfo.membership?.name || 'Không có gói tập' }}
          </a-descriptions-item>
          <a-descriptions-item label="Trạng thái">
            <a-tag :color="getMembershipStatusColor(membershipInfo.is_active)">
              {{ membershipInfo.is_active ? 'Đang hoạt động' : 'Hết hạn' }}
            </a-tag>
          </a-descriptions-item>
          <a-descriptions-item v-if="membershipInfo.end_date" label="Ngày hết hạn">
            {{ formatDate(membershipInfo.end_date) }}
          </a-descriptions-item>
          <a-descriptions-item v-if="membershipInfo.days_left !== null" label="Ngày còn lại">
            <a-tag :color="getDaysLeftColor(membershipInfo.days_left)">
              {{ membershipInfo.days_left }} ngày
            </a-tag>
          </a-descriptions-item>
        </a-descriptions>
      </div>

      <a-alert v-if="!canCheckIn" type="warning" show-icon :message="checkInMessage" />

      <div class="border-t pt-4 mt-4">
        <div class="text-sm font-medium mb-2">Chi tiết check-in:</div>
        <a-form layout="vertical">
          <a-form-item label="Thời gian check-in">
            <a-input :value="currentTime" disabled />
          </a-form-item>
          
          <a-form-item label="Ghi chú">
            <a-textarea
              v-model:value="checkInNote"
              :rows="3"
              placeholder="Nhập ghi chú về buổi tập..."
            />
          </a-form-item>
        </a-form>
      </div>
    </div>

    <template #footer>
      <a-button @click="handleCancel">Hủy</a-button>
      <a-button
        type="primary"
        :disabled="!canCheckIn"
        @click="handleConfirm"
        :loading="loading"
      >
        Xác nhận check-in
      </a-button>
    </template>
  </a-modal>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { message } from 'ant-design-vue';
import { useMemberApi } from '../../composables/useMemberApi';
import dayjs from 'dayjs';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  member: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close', 'confirm', 'update:isOpen']);

const { fetchMemberMembership, checkInMember, loading, error } = useMemberApi();

const membershipInfo = ref(null);
const checkInNote = ref('');
const currentTime = computed(() => dayjs().format('DD/MM/YYYY HH:mm:ss'));

// Kiểm tra xem hội viên có thể check-in hay không
const canCheckIn = computed(() => {
  if (!membershipInfo.value) return false;
  
  // Nếu gói còn hạn
  if (membershipInfo.value.is_active) {
    return true;
  }
  
  return false;
});

// Message để hiển thị khi không thể check-in
const checkInMessage = computed(() => {
  if (!membershipInfo.value) return 'Không tìm thấy thông tin gói tập';
  
  if (!membershipInfo.value.is_active) {
    return 'Gói tập đã hết hạn. Vui lòng gia hạn để tiếp tục sử dụng.';
  }
  
  return '';
});

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return dayjs(dateString).format('DD/MM/YYYY');
};

// Get color based on membership status
const getMembershipStatusColor = (isActive) => {
  return isActive ? 'green' : 'red';
};

// Get color based on days left
const getDaysLeftColor = (daysLeft) => {
  if (daysLeft <= 0) return 'red';
  if (daysLeft <= 7) return 'orange';
  if (daysLeft <= 30) return 'blue';
  return 'green';
};

// Load membership info when member changes
watch(() => props.member, async (newMember) => {
  if (newMember && newMember.id) {
    try {
      membershipInfo.value = await fetchMemberMembership(newMember.id);
    } catch (error) {
      message.error('Không thể tải thông tin gói tập');
      console.error(error);
    }
  } else {
    membershipInfo.value = null;
  }
}, { immediate: true });

// Reset form when modal opens
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    checkInNote.value = '';
  }
});

const handleCancel = () => {
  emit('update:isOpen', false);
  emit('close');
};

const handleConfirm = async () => {
  if (!props.member || !props.member.id) {
    message.error('Không có thông tin hội viên');
    return;
  }

  try {
    const checkInData = {
      check_in_time: dayjs().format('YYYY-MM-DD HH:mm:ss'),
      notes: checkInNote.value
    };

    const result = await checkInMember(props.member.id, checkInData);
    
    message.success('Check-in thành công');
    emit('confirm', result);
    emit('update:isOpen', false);
  } catch (error) {
    console.error('Check-in error:', error);
    message.error(error || 'Đã xảy ra lỗi khi check-in');
  }
};
</script> 