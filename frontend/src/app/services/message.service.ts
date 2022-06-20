import { Injectable } from "@angular/core";

import { NgxIzitoastService } from "ngx-izitoast";
import Swal from "sweetalert2";

@Injectable({
  providedIn: 'root'
})
export class MessageService {
  public swal = Swal;
  
  constructor(
    private iziToast: NgxIzitoastService
  ) { }

  public toastError(msg = 'Server Failure', title = 'Ops!') {
    this.iziToast.error({
      title: title,
      message: msg,
      position: 'topRight'
    });
  }

  public toastSuccess(msg = 'Registered Successfully!', title = 'Ready!') {
    this.iziToast.success({
      title: title,
      message: msg,
      position: 'topRight'
    });
  }

  public toastWarning(msg = 'Something is missing!', title = 'Opa!') {
    this.iziToast.warning({
      title: title,
      message: msg,
      position: 'topRight'
    });
  }

  public alertNet() {
    this.swal.fire({
      icon: 'error',
      title: 'Connection Fail',
      html: 'It looks like you have No Internet, please check the connection!',
      allowOutsideClick: false,
    }).then(resp => {
      if (resp.value) {
        location.reload();
      }
    });
  }

}
