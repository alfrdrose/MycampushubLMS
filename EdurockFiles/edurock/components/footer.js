// components/footer.js
function Footer() {
  return `
    
  <footer class="bg-[#021024] flex flex-col px-32 pt-14 pb-2 font-[Poppins] min-h-[338px]">
    <div class="flex flex-row">
      <div class="w-2/4  items-start px-4 flex flex-row justify-start gap-5">
        <div class="flex">
          <img src="assets/images/logo/au-logo.png" alt="Logo" class="h-12 w-auto" />
        </div>
        <div class="">
          <p class="text-lg font-semibold text-white pb-6">Arellano University</p>
          <p class="text-white text-md">Nurturing Minds, Shaping Futures.</p>
        </div>
      </div>
      <div class="w-1/4 justify-center items-center">
        <p class="text-white font-semibold mb-4">Quick Links</p>
        <div class="flex flex-col">
          <a href="#" class="text-white hover:text-primaryColor">Contact Us</a>
          <a href="#" class="text-white hover:text-primaryColor">About</a>
          <a href="#" class="text-white hover:text-primaryColor">Social Media</a>
        </div>
      </div>
      <div class=" w-1/4 justify-center items-center">
        <p class="text-white font-semibold pb-6">Other Links</p>
        <div class="flex flex-col">
          <a href="#" class="text-white hover:text-primaryColor">Data Privacy</a>
          <a href="#" class="text-white hover:text-primaryColor">Terms and Conditions</a>
          <a href="#" class="text-white hover:text-primaryColor">Request for Documents</a>
          <a href="#" class="text-white hover:text-primaryColor">Report Website Issues</a>
        </div>
      </div>
      <div class="w-1/4 justify-center items-center">
        <p class="text-white font-semibold pb-6">Social Media</p>
          <div class="flex flex-row gap-2">
            <a
              href="#"
              class="w-40.19px lg:w-35px 2xl:w-40.19px h-37px lg:h-35px 2xl:h-37px leading-37px lg:leading-35px 2xl:leading-37px text-whiteColor bg-whiteColor bg-opacity-10 hover:bg-primaryColor text-center"
              ><i class="icofont-facebook"></i
            ></a>
            <a
              href="#"
              class="w-40.19px lg:w-35px 2xl:w-40.19px h-37px lg:h-35px 2xl:h-37px leading-37px lg:leading-35px 2xl:leading-37px text-whiteColor bg-whiteColor bg-opacity-10 hover:bg-primaryColor text-center"
              ><i class="icofont-twitter"></i
            ></a>
            <a
              href="#"
              class="w-40.19px lg:w-35px 2xl:w-40.19px h-37px lg:h-35px 2xl:h-37px leading-37px lg:leading-35px 2xl:leading-37px text-whiteColor bg-whiteColor bg-opacity-10 hover:bg-primaryColor text-center"
              ><i class="icofont-vimeo"></i
            ></a>
            <a
              href="#"
              class="w-40.19px lg:w-35px 2xl:w-40.19px h-37px lg:h-35px 2xl:h-37px leading-37px lg:leading-35px 2xl:leading-37px text-whiteColor bg-whiteColor bg-opacity-10 hover:bg-primaryColor text-center"
              ><i class="icofont-linkedin"></i
            ></a>
            <a
              href="#"
              class="w-40.19px lg:w-35px 2xl:w-40.19px h-37px lg:h-35px 2xl:h-37px leading-37px lg:leading-35px 2xl:leading-37px text-whiteColor bg-whiteColor bg-opacity-10 hover:bg-primaryColor text-center"
              ><i class="icofont-skype"></i
            ></a>
          </div>
      </div>
    </div>

    <div class="border-t border-gray-300 mt-12 p-10 text-center">
      <p class="text-white text-sm">&copy; 2025 Arellano University. All rights reserved.</p>
    </div>
  </footer>
  `;
}

document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("footer").innerHTML = Footer();
});
