import Swal from 'sweetalert2'

const swal = {
  confirm: async (text, type = 'default') => {
    const isDelete = type === 'delete'

    return await Swal.fire({
      title: 'Are you sure?',
      text,
      icon: 'question',
      iconColor: '#1E4F8A',

      showCancelButton: true,
      confirmButtonText: 'Yes',
      cancelButtonText: 'Cancel',

      buttonsStyling: false,

      customClass: {
        popup:
          'rounded-xl px-6 py-7 shadow-[0_15px_40px_rgba(15,23,42,0.18)]',

        icon:
          'border-0 text-[#1E4F8A]',

        title:
          'text-[15px] font-medium leading-6 text-[#172033]',

        htmlContainer:
          'text-[13px] leading-6 text-slate-600',

        actions:
          'mt-6 flex justify-center gap-2.5',

        cancelButton:
          'min-w-[90px] rounded-lg bg-slate-100 px-5 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200',

        confirmButton: isDelete
          ? 'min-w-[90px] rounded-lg bg-red-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-red-700'
          : 'min-w-[90px] rounded-lg bg-green-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-green-700',
      },

      backdrop: 'rgba(15, 23, 42, 0.4)',

      didOpen: () => {
        const container = document.querySelector('.swal2-container')

        if (container) {
          container.style.zIndex = '99999999'
        }
      },
    })
  },

  success: async (title, text) => {
    return await Swal.fire({
      title,
      text,
      icon: 'success',

      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true,

      customClass: {
        popup:
          'rounded-xl px-6 py-7 shadow-[0_15px_40px_rgba(15,23,42,0.18)] cursor-pointer',

        icon:
          'text-green-600',

        title:
          'text-[15px] font-semibold text-[#172033]',

        htmlContainer:
          'text-[13px] leading-6 text-slate-600',
      },

      backdrop: 'rgba(15, 23, 42, 0.4)',

      didOpen: popup => {
        const container = document.querySelector('.swal2-container')

        if (container) {
          container.style.zIndex = '99999999'
        }

        popup.style.cursor = 'pointer'

        popup.addEventListener('click', () => {
          Swal.close()
        })
      },
    })
  },

  error: async (title, text) => {
    return await Swal.fire({
      title,
      text,
      icon: 'error',

      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true,

      customClass: {
        popup:
          'rounded-xl px-6 py-7 shadow-[0_15px_40px_rgba(15,23,42,0.18)] cursor-pointer',

        icon:
          'text-red-600',

        title:
          'text-[15px] font-semibold text-[#172033]',

        htmlContainer:
          'text-[13px] leading-6 text-slate-600',
      },

      backdrop: 'rgba(15, 23, 42, 0.4)',

      didOpen: popup => {
        const container = document.querySelector('.swal2-container')

        if (container) {
          container.style.zIndex = '99999999'
        }

        popup.style.cursor = 'pointer'

        popup.addEventListener('click', () => {
          Swal.close()
        })
      },
    })
  },
}

export default swal
